<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Billing;
use App\Models\Link;
use App\Models\SurveyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function index()
    {
        $billings = Billing::with(['link.customer', 'link.reseller', 'completedByUser'])
            ->latest()
            ->paginate(15);

        $pendingSurveys = SurveyRequest::where('status', SurveyRequest::STATUS_BILLING_PENDING)
            ->with(['customer', 'reseller', 'feedback', 'workOrder.link'])
            ->latest()
            ->get();

        return view('billing.index', compact('billings', 'pendingSurveys'));
    }

    public function process(Request $request, SurveyRequest $survey)
    {
        $link = $survey->workOrder?->link;

        if (! $link) {
            return back()->with('error', 'No link record found for this survey request.');
        }

        $validated = $request->validate([
            'installation_charge' => ['required', 'numeric', 'min:0'],
            'monthly_charge' => ['required', 'numeric', 'min:0'],
            'nttn_cost' => ['required', 'numeric', 'min:0'],
            'billing_start_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:billing_start_date'],
        ]);

        $invoiceNo = 'INV-' . date('Y') . '-' . strtoupper(Str::random(6));

        $billing = Billing::updateOrCreate(
            ['link_id' => $link->id],
            array_merge($validated, [
                'invoice_no' => $invoiceNo,
                'status' => Billing::STATUS_COMPLETED,
                'completed_by' => Auth::id(),
            ])
        );

        $oldStatus = $survey->status;
        $survey->update(['status' => SurveyRequest::STATUS_FINAL_MD_APPROVAL]);

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey->id,
            action: 'BILLING_COMPLETED',
            fromStatus: $oldStatus,
            toStatus: SurveyRequest::STATUS_FINAL_MD_APPROVAL,
            remarks: "Commercial invoicing completed. Invoice {$invoiceNo} created. Transferred for MD Final Activation sign-off.",
            userId: Auth::id()
        );

        return redirect()->route('surveys.show', $survey)
            ->with('success', "Billing confirmed and Invoice {$invoiceNo} issued! Sent to MD for Final Approval.");
    }
}
