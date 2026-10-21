<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Billing;
use App\Models\Link;
use App\Models\Reseller;
use App\Models\SurveyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isReseller = $user->isReseller();
        $resellerId = $user->reseller?->id ?? 0;

        // 1. Active Circuits & Total Consolidated Bill
        $activeLinksQuery = Link::with(['customer', 'reseller', 'billing'])
            ->where('status', Link::STATUS_ACTIVE);

        if ($isReseller) {
            // Strictly enforce reseller's own circuits only - prevents seeing other resellers' bills
            $activeLinksQuery->where('reseller_id', $resellerId);
        } else {
            // Billing, MD, and Super Admin see all resellers by default, with optional filter
            if ($request->filled('reseller_id')) {
                $activeLinksQuery->where('reseller_id', $request->reseller_id);
            }
        }

        $activeLinks = $activeLinksQuery->latest()->get();

        $totalActiveCircuits = $activeLinks->count();
        $totalMonthlyBill = (float) $activeLinks->sum(fn ($l) => (float) ($l->billing?->monthly_charge ?? 0));
        $totalInstallationBill = (float) $activeLinks->sum(fn ($l) => (float) ($l->billing?->installation_charge ?? 0));
        $totalActiveBandwidth = (int) $activeLinks->sum('bandwidth_mbps');

        // 2. Commercial Invoices Ledger
        $billingsQuery = Billing::with(['link.customer', 'link.reseller', 'completedByUser']);
        if ($isReseller) {
            $billingsQuery->whereHas('link', fn ($q) => $q->where('reseller_id', $resellerId));
        } else {
            if ($request->filled('reseller_id')) {
                $billingsQuery->whereHas('link', fn ($q) => $q->where('reseller_id', $request->reseller_id));
            }
        }
        $billings = $billingsQuery->latest()->paginate(15)->withQueryString();

        // 3. Pending Initial Billing (Internal staff only)
        $pendingSurveys = collect();
        if (! $isReseller) {
            $pendingSurveysQuery = SurveyRequest::where('status', SurveyRequest::STATUS_BILLING_PENDING)
                ->with(['customer', 'reseller', 'feedback', 'workOrder.link']);

            if ($request->filled('reseller_id')) {
                $pendingSurveysQuery->where('reseller_id', $request->reseller_id);
            }

            $pendingSurveys = $pendingSurveysQuery->latest()->get();
        }

        $resellers = (! $isReseller) ? Reseller::orderBy('company_name')->get() : collect();
        $selectedResellerId = (! $isReseller && $request->filled('reseller_id')) ? (int) $request->reseller_id : null;

        return view('billing.index', compact(
            'billings',
            'pendingSurveys',
            'activeLinks',
            'totalActiveCircuits',
            'totalMonthlyBill',
            'totalInstallationBill',
            'totalActiveBandwidth',
            'isReseller',
            'resellers',
            'selectedResellerId'
        ));
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

        $oldStatus = $survey->status;
        $isCorrection = ($oldStatus === SurveyRequest::STATUS_BILLING_PENDING && $link->billing?->status === 'RETURNED');
        $invoiceNo = $link->billing?->invoice_no ?? ('INV-'.date('Y').'-'.strtoupper(Str::random(6)));

        $billing = Billing::updateOrCreate(
            ['link_id' => $link->id],
            array_merge($validated, [
                'invoice_no' => $invoiceNo,
                'status' => Billing::STATUS_COMPLETED,
                'completed_by' => Auth::id(),
            ])
        );

        $survey->update(['status' => SurveyRequest::STATUS_FINAL_MD_APPROVAL]);

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey->id,
            action: $isCorrection ? 'BILLING_REVISED' : 'BILLING_COMPLETED',
            fromStatus: $oldStatus,
            toStatus: SurveyRequest::STATUS_FINAL_MD_APPROVAL,
            remarks: ($isCorrection ? 'Corrected commercial invoicing submitted.' : 'Commercial invoicing completed.')." Invoice {$invoiceNo} ".($isCorrection ? 'updated' : 'created').'. Transferred for MD Final Activation sign-off.',
            userId: Auth::id()
        );

        return redirect()->route('surveys.show', $survey)
            ->with('success', $isCorrection ? "Corrected billing confirmed and Invoice {$invoiceNo} re-submitted to MD for Final Approval!" : "Billing confirmed and Invoice {$invoiceNo} issued! Sent to MD for Final Approval.");
    }
}
