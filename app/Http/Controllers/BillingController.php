<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Approval;
use App\Models\Billing;
use App\Models\BillingRateRevision;
use App\Models\ChatRoom;
use App\Models\Customer;
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
        $activeLinksQuery = Link::with(['customer', 'reseller', 'billing', 'pendingRateRevision'])
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

        // 4. Legacy Data Entries
        $legacyLinksQuery = Link::with(['customer', 'reseller', 'billing', 'approvals.approver'])
            ->where('is_legacy', true);

        if ($isReseller) {
            $legacyLinksQuery->where('reseller_id', $resellerId);
        } else {
            if ($request->filled('reseller_id')) {
                $legacyLinksQuery->where('reseller_id', $request->reseller_id);
            }
        }

        $legacyLinks = $legacyLinksQuery->latest()->get();

        // 5. Rate Revisions Ledger
        $rateRevisionsQuery = BillingRateRevision::with(['link.customer', 'link.reseller', 'requester', 'approver'])
            ->latest();

        if ($isReseller) {
            $rateRevisionsQuery->whereHas('link', fn ($q) => $q->where('reseller_id', $resellerId))
                ->where('status', BillingRateRevision::STATUS_APPROVED);
        } else {
            if ($request->filled('reseller_id')) {
                $rateRevisionsQuery->whereHas('link', fn ($q) => $q->where('reseller_id', $request->reseller_id));
            }
        }

        $rateRevisions = $rateRevisionsQuery->take(20)->get();

        $resellers = (! $isReseller) ? Reseller::orderBy('company_name')->get() : collect();
        $selectedResellerId = (! $isReseller && $request->filled('reseller_id')) ? (int) $request->reseller_id : null;

        return view('billing.index', compact(
            'billings',
            'pendingSurveys',
            'activeLinks',
            'legacyLinks',
            'rateRevisions',
            'totalActiveCircuits',
            'totalMonthlyBill',
            'totalInstallationBill',
            'totalActiveBandwidth',
            'isReseller',
            'resellers',
            'selectedResellerId'
        ));
    }

    public function createLegacy()
    {
        $user = Auth::user();
        if (! ($user->isBilling() || $user->isMd() || $user->isSuperAdmin())) {
            abort(403, 'Unauthorized access to legacy billing entry desk.');
        }

        $resellers = Reseller::with('customers')->orderBy('company_name')->get();

        return view('billing.legacy_create', compact('resellers'));
    }

    public function storeLegacy(Request $request)
    {
        $user = Auth::user();
        if (! ($user->isBilling() || $user->isMd() || $user->isSuperAdmin())) {
            abort(403, 'Unauthorized access to legacy billing entry desk.');
        }

        $validated = $request->validate([
            'reseller_id' => ['required', 'exists:resellers,id'],
            'customer_mode' => ['required', 'in:existing,new'],
            'customer_id' => ['required_if:customer_mode,existing', 'nullable', 'exists:customers,id'],
            'customer_name' => ['required_if:customer_mode,new', 'nullable', 'string', 'max:200'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_district' => ['nullable', 'string', 'max:100'],
            'customer_thana' => ['nullable', 'string', 'max:100'],
            'customer_address' => ['nullable', 'string'],
            'circuit_id' => ['nullable', 'string', 'max:120'],
            'link_id' => ['nullable', 'string', 'max:60', 'unique:links,link_id'],
            'bandwidth_mbps' => ['required', 'integer', 'min:1'],
            'nttn_provider' => ['required', 'string', 'max:120'],
            'nttn_link_id' => ['nullable', 'string', 'max:120'],
            'vlan' => ['nullable', 'string', 'max:100'],
            'port' => ['nullable', 'string', 'max:100'],
            'activation_date' => ['required', 'date'],
            'monthly_charge' => ['required', 'numeric', 'min:0'],
            'installation_charge' => ['nullable', 'numeric', 'min:0'],
            'nttn_cost' => ['nullable', 'numeric', 'min:0'],
            'invoice_no' => ['nullable', 'string', 'max:80', 'unique:billing,invoice_no'],
            'billing_start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'md_approval_ref' => ['nullable', 'string', 'max:120'],
            'md_approval_doc' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'legacy_notes' => ['nullable', 'string'],
            'direct_approve' => ['nullable', 'boolean'],
        ]);

        $reseller = Reseller::findOrFail($validated['reseller_id']);

        // Resolve or create Customer
        if ($validated['customer_mode'] === 'new') {
            $customer = Customer::create([
                'reseller_id' => $reseller->id,
                'name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'] ?? null,
                'district' => $validated['customer_district'] ?? 'Dhaka',
                'thana' => $validated['customer_thana'] ?? null,
                'address' => $validated['customer_address'] ?? null,
            ]);
        } else {
            $customer = Customer::where('reseller_id', $reseller->id)->findOrFail($validated['customer_id']);
        }

        // Generate identifiers if omitted
        $linkId = ! empty($validated['link_id'])
            ? $validated['link_id']
            : ('LNK-LEG-'.strtoupper(Str::random(6)));

        $circuitId = ! empty($validated['circuit_id'])
            ? $validated['circuit_id']
            : ('CIR-LEG-'.strtoupper(Str::random(6)));

        $invoiceNo = ! empty($validated['invoice_no'])
            ? $validated['invoice_no']
            : ('INV-LEG-'.date('Y').'-'.strtoupper(Str::random(5)));

        // Handle MD Approval document upload
        $docPath = null;
        if ($request->hasFile('md_approval_doc')) {
            $docPath = $request->file('md_approval_doc')->store('approvals', 'public');
        }

        $canDirectApprove = ($user->isMd() || $user->isSuperAdmin()) && $request->boolean('direct_approve');
        $initialLinkStatus = $canDirectApprove ? Link::STATUS_ACTIVE : Link::STATUS_PENDING;
        $initialBillingStatus = $canDirectApprove ? Billing::STATUS_COMPLETED : Billing::STATUS_PENDING;

        // Create Link
        $link = Link::create([
            'link_id' => $linkId,
            'circuit_id' => $circuitId,
            'customer_id' => $customer->id,
            'reseller_id' => $reseller->id,
            'work_order_id' => null,
            'bandwidth_mbps' => $validated['bandwidth_mbps'],
            'nttn_provider' => $validated['nttn_provider'],
            'nttn_link_id' => $validated['nttn_link_id'] ?? null,
            'vlan' => $validated['vlan'] ?? null,
            'port' => $validated['port'] ?? null,
            'status' => $initialLinkStatus,
            'is_legacy' => true,
            'md_approval_ref' => $validated['md_approval_ref'] ?? null,
            'md_approval_doc' => $docPath,
            'legacy_notes' => $validated['legacy_notes'] ?? null,
            'activation_date' => $validated['activation_date'],
        ]);

        // Create Billing Record
        $billing = Billing::create([
            'link_id' => $link->id,
            'installation_charge' => $validated['installation_charge'] ?? 0,
            'monthly_charge' => $validated['monthly_charge'],
            'nttn_cost' => $validated['nttn_cost'] ?? 0,
            'billing_start_date' => $validated['billing_start_date'] ?? $validated['activation_date'],
            'due_date' => $validated['due_date'] ?? null,
            'invoice_no' => $invoiceNo,
            'status' => $initialBillingStatus,
            'is_legacy' => true,
            'notes' => $validated['legacy_notes'] ?? null,
            'completed_by' => $canDirectApprove ? $user->id : null,
        ]);

        if ($canDirectApprove) {
            ChatRoom::firstOrCreate(['link_id' => $link->id]);

            Approval::create([
                'entity_type' => 'link',
                'entity_id' => $link->id,
                'approval_stage' => Approval::STAGE_MD_LEGACY_BILLING,
                'status' => Approval::STATUS_APPROVED,
                'remarks' => $validated['legacy_notes'] ?? 'Directly approved and activated by MD.',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            ActivityLog::record(
                entityType: 'link',
                entityId: $link->id,
                action: 'LEGACY_LINK_DIRECT_ACTIVATED',
                fromStatus: Link::STATUS_PENDING,
                toStatus: Link::STATUS_ACTIVE,
                remarks: "Manual legacy circuit {$circuitId} ({$linkId}) directly approved by MD for {$reseller->company_name}. Live in reseller portal.",
                userId: $user->id
            );

            return redirect()->route('billing.index')
                ->with('success', "Legacy Circuit {$circuitId} and Invoice {$invoiceNo} directly approved & activated! Now live in {$reseller->company_name}'s reseller portal.");
        }

        // Submitting for MD Approval
        Approval::create([
            'entity_type' => 'link',
            'entity_id' => $link->id,
            'approval_stage' => Approval::STAGE_MD_LEGACY_BILLING,
            'status' => Approval::STATUS_PENDING,
            'remarks' => ($validated['md_approval_ref'] ? "Ref: {$validated['md_approval_ref']}. " : '').($validated['legacy_notes'] ?? 'Manual legacy circuit and billing entry awaiting MD approval.'),
        ]);

        ActivityLog::record(
            entityType: 'link',
            entityId: $link->id,
            action: 'LEGACY_LINK_SUBMITTED_FOR_MD',
            fromStatus: null,
            toStatus: Link::STATUS_PENDING,
            remarks: "Billing department entered manual legacy circuit {$circuitId} for {$reseller->company_name}. Submitted for MD approval.",
            userId: $user->id
        );

        return redirect()->route('billing.index')
            ->with('success', "Legacy Circuit {$circuitId} logged successfully! Forwarded to MD Sir's approval desk for final sign-off.");
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

    public function editRate(Link $link)
    {
        $user = Auth::user();
        if (! ($user->isBilling() || $user->isMd() || $user->isSuperAdmin())) {
            abort(403, 'Unauthorized access to circuit rate editing.');
        }

        $link->load(['customer', 'reseller', 'billing', 'pendingRateRevision', 'rateRevisions.requester', 'rateRevisions.approver']);

        return view('billing.edit_rate', compact('link'));
    }

    public function submitRateRevision(Request $request, Link $link)
    {
        $user = Auth::user();
        if (! ($user->isBilling() || $user->isMd() || $user->isSuperAdmin())) {
            abort(403, 'Unauthorized access to circuit rate revision.');
        }

        $validated = $request->validate([
            'monthly_charge' => ['required', 'numeric', 'min:0'],
            'installation_charge' => ['nullable', 'numeric', 'min:0'],
            'nttn_cost' => ['nullable', 'numeric', 'min:0'],
            'effective_date' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'direct_approve' => ['nullable', 'boolean'],
        ]);

        $billing = $link->billing ?? Billing::firstOrCreate(['link_id' => $link->id], [
            'installation_charge' => 0,
            'monthly_charge' => 0,
            'status' => Billing::STATUS_COMPLETED,
            'invoice_no' => 'INV-'.date('Y').'-'.strtoupper(Str::random(6)),
        ]);

        $canDirectApprove = ($user->isMd() || $user->isSuperAdmin()) && $request->boolean('direct_approve');

        $circuitCode = $link->circuit_id ?: $link->link_id;

        if ($canDirectApprove) {
            $oldMonthly = (float) $billing->monthly_charge;
            $oldInstallation = (float) $billing->installation_charge;
            $oldNttnCost = (float) $billing->nttn_cost;

            $newMonthly = (float) $validated['monthly_charge'];
            $newInstallation = isset($validated['installation_charge']) ? (float) $validated['installation_charge'] : $oldInstallation;
            $newNttnCost = isset($validated['nttn_cost']) ? (float) $validated['nttn_cost'] : $oldNttnCost;

            $billing->update([
                'monthly_charge' => $newMonthly,
                'installation_charge' => $newInstallation,
                'nttn_cost' => $newNttnCost,
                'completed_by' => $user->id,
            ]);

            BillingRateRevision::create([
                'link_id' => $link->id,
                'billing_id' => $billing->id,
                'requested_by' => $user->id,
                'approved_by' => $user->id,
                'old_monthly_charge' => $oldMonthly,
                'new_monthly_charge' => $newMonthly,
                'old_installation_charge' => $oldInstallation,
                'new_installation_charge' => $newInstallation,
                'old_nttn_cost' => $oldNttnCost,
                'new_nttn_cost' => $newNttnCost,
                'effective_date' => $validated['effective_date'] ?? now()->toDateString(),
                'reason' => $validated['reason'],
                'status' => BillingRateRevision::STATUS_APPROVED,
                'remarks' => 'Directly approved and applied by MD.',
                'approved_at' => now(),
            ]);

            Approval::create([
                'entity_type' => 'rate_revision',
                'entity_id' => $link->id,
                'approval_stage' => Approval::STAGE_MD_RATE_REVISION,
                'status' => Approval::STATUS_APPROVED,
                'remarks' => "Circuit {$circuitCode} rate directly updated: ৳ ".number_format($oldMonthly, 2).' → ৳ '.number_format($newMonthly, 2).". Reason: {$validated['reason']}",
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            ActivityLog::record(
                entityType: 'link',
                entityId: $link->id,
                action: 'CIRCUIT_RATE_DIRECTLY_UPDATED',
                fromStatus: null,
                toStatus: null,
                remarks: "Circuit {$circuitCode} rate updated from ৳ ".number_format($oldMonthly, 2).' to ৳ '.number_format($newMonthly, 2).". Live in {$link->reseller->company_name}'s portal.",
                userId: $user->id
            );

            return redirect()->route('billing.index')
                ->with('success', "Rate for Circuit {$circuitCode} directly updated to ৳ ".number_format($newMonthly, 2)." and is now live in {$link->reseller->company_name}'s portal!");
        }

        // Submitting for MD Approval: Keep current billing rate unchanged!
        BillingRateRevision::where('link_id', $link->id)
            ->where('status', BillingRateRevision::STATUS_PENDING)
            ->update([
                'status' => BillingRateRevision::STATUS_REJECTED,
                'remarks' => 'Superseded by newer revision request.',
            ]);

        $revision = BillingRateRevision::create([
            'link_id' => $link->id,
            'billing_id' => $billing->id,
            'requested_by' => $user->id,
            'approved_by' => null,
            'old_monthly_charge' => (float) $billing->monthly_charge,
            'new_monthly_charge' => (float) $validated['monthly_charge'],
            'old_installation_charge' => (float) $billing->installation_charge,
            'new_installation_charge' => isset($validated['installation_charge']) ? (float) $validated['installation_charge'] : (float) $billing->installation_charge,
            'old_nttn_cost' => (float) $billing->nttn_cost,
            'new_nttn_cost' => isset($validated['nttn_cost']) ? (float) $validated['nttn_cost'] : (float) $billing->nttn_cost,
            'effective_date' => $validated['effective_date'] ?? now()->toDateString(),
            'reason' => $validated['reason'],
            'status' => BillingRateRevision::STATUS_PENDING,
        ]);

        Approval::create([
            'entity_type' => 'rate_revision',
            'entity_id' => $revision->id,
            'approval_stage' => Approval::STAGE_MD_RATE_REVISION,
            'status' => Approval::STATUS_PENDING,
            'remarks' => "Proposed rate revision for Circuit {$circuitCode}: ৳ ".number_format($billing->monthly_charge, 2).' → ৳ '.number_format($validated['monthly_charge'], 2).". Reason: {$validated['reason']}",
        ]);

        ActivityLog::record(
            entityType: 'link',
            entityId: $link->id,
            action: 'CIRCUIT_RATE_REVISION_REQUESTED',
            fromStatus: null,
            toStatus: null,
            remarks: "Billing requested rate revision for Circuit {$circuitCode}: ৳ ".number_format($billing->monthly_charge, 2).' → ৳ '.number_format($validated['monthly_charge'], 2).'. Awaiting MD approval.',
            userId: $user->id
        );

        return redirect()->route('billing.index')
            ->with('success', "Rate revision for Circuit {$circuitCode} submitted (৳ ".number_format($billing->monthly_charge, 2).' → ৳ '.number_format($validated['monthly_charge'], 2).'). Forwarded to MD Sir for executive approval.');
    }
}
