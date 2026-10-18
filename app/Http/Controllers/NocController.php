<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\NocHandover;
use App\Models\NocTest;
use App\Models\SurveyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NocController extends Controller
{
    public function index()
    {
        $handovers = NocHandover::with(['workOrder.surveyRequest.customer', 'link', 'test.tester', 'verifier'])
            ->latest()
            ->paginate(15);

        return view('noc.index', compact('handovers'));
    }

    public function show(NocHandover $handover)
    {
        $handover->load(['workOrder.surveyRequest.customer', 'workOrder.surveyRequest.reseller', 'link', 'test.tester']);
        return view('noc.show', compact('handover'));
    }

    public function startTesting(NocHandover $handover)
    {
        $handover->update(['status' => NocHandover::STATUS_TESTING]);
        $handover->workOrder->surveyRequest->update(['status' => SurveyRequest::STATUS_NOC_TESTING]);

        ActivityLog::record(
            entityType: 'noc_handover',
            entityId: $handover->id,
            action: 'NOC_TESTING_STARTED',
            fromStatus: NocHandover::STATUS_RECEIVED,
            toStatus: NocHandover::STATUS_TESTING,
            remarks: 'NOC engineers started 8-point SLA verification tests',
            userId: Auth::id()
        );

        return back()->with('info', 'Testing started. Complete the 8-point checklist.');
    }

    public function submitTest(Request $request, NocHandover $handover)
    {
        $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        $physicalLink = $request->boolean('physical_link');
        $opticalPower = $request->boolean('optical_power');
        $vlanOk = $request->boolean('vlan_ok');
        $bandwidthOk = $request->boolean('bandwidth_ok');
        $pingOk = $request->boolean('ping_ok');
        $latencyOk = $request->boolean('latency_ok');
        $packetLossOk = $request->boolean('packet_loss_ok');
        $monitoringOk = $request->boolean('monitoring_ok');

        $allPassed = $physicalLink && $opticalPower && $vlanOk && $bandwidthOk
            && $pingOk && $latencyOk && $packetLossOk && $monitoringOk;

        $result = $allPassed ? NocTest::RESULT_PASSED : NocTest::RESULT_FAILED;

        $test = NocTest::updateOrCreate(
            ['noc_handover_id' => $handover->id],
            [
                'physical_link' => $physicalLink,
                'optical_power' => $opticalPower,
                'vlan_ok' => $vlanOk,
                'bandwidth_ok' => $bandwidthOk,
                'ping_ok' => $pingOk,
                'latency_ok' => $latencyOk,
                'packet_loss_ok' => $packetLossOk,
                'monitoring_ok' => $monitoringOk,
                'result' => $result,
                'remarks' => $request->remarks,
                'tested_by' => Auth::id(),
            ]
        );

        $survey = $handover->workOrder->surveyRequest;

        if ($allPassed) {
            $handover->update([
                'status' => NocHandover::STATUS_VERIFIED,
                'verified_at' => now(),
                'verified_by' => Auth::id(),
            ]);

            $oldStatus = $survey->status;
            $survey->update(['status' => SurveyRequest::STATUS_BILLING_PENDING]);

            ActivityLog::record(
                entityType: 'survey_request',
                entityId: $survey->id,
                action: 'NOC_VERIFIED',
                fromStatus: $oldStatus,
                toStatus: SurveyRequest::STATUS_BILLING_PENDING,
                remarks: "All 8 SLA tests passed by NOC (" . Auth::user()->name . "). Escalated to Billing.",
                userId: Auth::id()
            );

            return redirect()->route('surveys.show', $survey)
                ->with('success', 'NOC 8-point tests PASSED! Transferred to Billing department.');
        } else {
            $handover->update([
                'status' => NocHandover::STATUS_REJECTED,
                'remarks' => $request->remarks,
            ]);

            ActivityLog::record(
                entityType: 'noc_handover',
                entityId: $handover->id,
                action: 'NOC_TEST_FAILED',
                fromStatus: NocHandover::STATUS_TESTING,
                toStatus: NocHandover::STATUS_REJECTED,
                remarks: "NOC tests failed: {$request->remarks}",
                userId: Auth::id()
            );

            return back()->with('error', 'NOC verification failed. Check list items that did not meet requirements.');
        }
    }
}
