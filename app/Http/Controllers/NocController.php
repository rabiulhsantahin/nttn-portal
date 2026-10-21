<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\NocHandover;
use App\Models\NocTest;
use App\Models\SurveyRequest;
use App\Models\Ticket;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
            'action' => ['nullable', 'string', 'in:verify,reject'],
        ]);

        $action = $request->input('action', 'verify');

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

        $survey = $handover->workOrder->surveyRequest;

        // If NOC clicked 'Issue TT & Send Back to Tx' OR if any checkbox test failed
        if ($action === 'reject' || ! $allPassed) {
            $reason = $request->remarks ?: 'Optical / physical SLA tests did not meet required threshold. Field re-splicing needed.';

            // 1. Record test result
            NocTest::updateOrCreate(
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
                    'result' => NocTest::RESULT_FAILED,
                    'remarks' => $reason,
                    'tested_by' => Auth::id(),
                ]
            );

            // 2. Generate Trouble Ticket for Transmission Team
            $ttNo = 'TT-'.date('Y').'-'.strtoupper(Str::random(6));
            Ticket::create([
                'ticket_no' => $ttNo,
                'link_id' => $handover->link_id,
                'created_by' => Auth::id(),
                'assigned_to' => $handover->workOrder->assigned_to,
                'type' => 'NOC Handover Rejection / Field Splicing Rework',
                'priority' => Ticket::PRIORITY_HIGH,
                'description' => "NOC Handover failed SLA verification. Returned to Transmission team for field jointing and re-splicing. Remarks: {$reason}",
                'status' => Ticket::STATUS_OPEN,
            ]);

            // 3. Mark Handover as REJECTED
            $handover->update([
                'status' => NocHandover::STATUS_REJECTED,
                'remarks' => "TT [{$ttNo}]: {$reason}",
            ]);

            // 4. Reset Work Order status to WORK_IN_PROGRESS so Tx can rework
            $handover->workOrder->update([
                'status' => WorkOrder::STATUS_WORK_IN_PROGRESS,
            ]);

            // 5. Update Survey Request status back to WORK_IN_PROGRESS
            $oldStatus = $survey->status;
            $survey->update(['status' => SurveyRequest::STATUS_WORK_IN_PROGRESS]);

            ActivityLog::record(
                entityType: 'survey_request',
                entityId: $survey->id,
                action: 'NOC_HANDOVER_REJECTED_TT_DISPATCHED',
                fromStatus: $oldStatus,
                toStatus: SurveyRequest::STATUS_WORK_IN_PROGRESS,
                remarks: "NOC rejected handover and dispatched Trouble Ticket {$ttNo} to Transmission team (".($handover->workOrder->assignedEngineer?->name ?? 'Tx Team')."). Reason: {$reason}",
                userId: Auth::id()
            );

            return redirect()->route('surveys.show', $survey)
                ->with('warning', "Handover returned to Transmission team! Trouble Ticket {$ttNo} issued for field rework.");
        }

        // All passed: Mark verified and transfer to billing
        NocTest::updateOrCreate(
            ['noc_handover_id' => $handover->id],
            [
                'physical_link' => true,
                'optical_power' => true,
                'vlan_ok' => true,
                'bandwidth_ok' => true,
                'ping_ok' => true,
                'latency_ok' => true,
                'packet_loss_ok' => true,
                'monitoring_ok' => true,
                'result' => NocTest::RESULT_PASSED,
                'remarks' => $request->remarks ?: 'Full optical & throughput validation completed successfully.',
                'tested_by' => Auth::id(),
            ]
        );

        $handover->update([
            'status' => NocHandover::STATUS_VERIFIED,
            'verified_at' => now(),
            'verified_by' => Auth::id(),
        ]);

        // Resolve any open handover rejection tickets for this link
        if ($handover->link_id) {
            Ticket::where('link_id', $handover->link_id)
                ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_WORKING])
                ->where('type', 'NOC Handover Rejection / Field Splicing Rework')
                ->update([
                    'status' => Ticket::STATUS_RESOLVED,
                    'resolved_at' => now(),
                ]);
        }

        $oldStatus = $survey->status;
        $survey->update(['status' => SurveyRequest::STATUS_BILLING_PENDING]);

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey->id,
            action: 'NOC_VERIFIED',
            fromStatus: $oldStatus,
            toStatus: SurveyRequest::STATUS_BILLING_PENDING,
            remarks: 'All 8 SLA tests passed by NOC ('.Auth::user()->name.'). Escalated to Billing.',
            userId: Auth::id()
        );

        return redirect()->route('surveys.show', $survey)
            ->with('success', 'NOC 8-point tests PASSED! Transferred to Billing department.');
    }
}
