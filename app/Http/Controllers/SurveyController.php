<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Role;
use App\Models\SurveyFeedback;
use App\Models\SurveyRequest;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SurveyController extends Controller
{
    public function index(Request $request)
    {
        SurveyRequest::pruneExpiredRecords(30);

        $user = Auth::user();
        $role = $user->role->name;

        $query = SurveyRequest::with(['customer', 'reseller', 'feedback', 'workOrder.assignedEngineer']);

        // Resellers only see their own surveys
        if ($role === Role::RESELLER) {
            $query->where('reseller_id', $user->reseller?->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('district', 'like', "%{$search}%");
                    });
            });
        }

        $surveys = $query->latest()->paginate(15);

        return view('surveys.index', compact('surveys', 'role'));
    }

    public function create()
    {
        $user = Auth::user();
        $reseller = $user->reseller;

        if (! $reseller && ! $user->isSuperAdmin()) {
            return redirect()->route('surveys.index')
                ->with('error', 'Only registered resellers can initiate survey requests.');
        }

        $customers = $reseller ? $reseller->customers : Customer::all();

        return view('surveys.create', compact('customers', 'reseller'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $reseller = $user->reseller;

        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required_without:customer_id', 'nullable', 'string', 'max:200'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_address' => ['nullable', 'string'],
            'district' => ['nullable', 'string', 'max:100'],
            'thana' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'bandwidth_mbps' => ['required', 'integer', 'min:1'],
            'service_type' => ['required', 'string', 'max:80'],
            'preferred_nttn' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
        ]);

        // Create customer if new
        if (empty($validated['customer_id'])) {
            $customer = Customer::create([
                'reseller_id' => $reseller ? $reseller->id : 1,
                'name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'] ?? null,
                'address' => $validated['customer_address'] ?? null,
                'district' => $validated['district'] ?? null,
                'thana' => $validated['thana'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ]);
            $customerId = $customer->id;
        } else {
            $customerId = $validated['customer_id'];
            if ($reseller) {
                $customerExists = Customer::where('id', $customerId)
                    ->where('reseller_id', $reseller->id)
                    ->exists();
                if (! $customerExists) {
                    abort(403, 'Unauthorized customer selection.');
                }
            }
        }

        $requestNo = 'SRV-'.date('Y').'-'.strtoupper(Str::random(6));

        $survey = SurveyRequest::create([
            'request_no' => $requestNo,
            'reseller_id' => $reseller ? $reseller->id : 1,
            'customer_id' => $customerId,
            'bandwidth_mbps' => $validated['bandwidth_mbps'],
            'service_type' => $validated['service_type'],
            'preferred_nttn' => $validated['preferred_nttn'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => SurveyRequest::STATUS_SURVEY_REQUESTED,
            'requested_at' => now(),
        ]);

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey->id,
            action: 'SURVEY_REQUESTED',
            fromStatus: null,
            toStatus: SurveyRequest::STATUS_SURVEY_REQUESTED,
            remarks: "New survey request lodged ({$validated['bandwidth_mbps']} Mbps)",
            userId: $user->id
        );

        return redirect()->route('surveys.show', $survey)
            ->with('success', "Survey request {$requestNo} created successfully!");
    }

    public function show(SurveyRequest $survey)
    {
        $user = Auth::user();
        if ($user->role->name === Role::RESELLER && $survey->reseller_id !== $user->reseller?->id) {
            abort(403, 'Unauthorized access to this survey request.');
        }

        $survey->load([
            'customer',
            'reseller.user',
            'feedback.creator',
            'feedbacks.creator',
            'workOrder.assignedEngineer',
            'workOrder.link.billing',
            'workOrder.nocHandover.test.tester',
            'workOrder.nocHandover.verifier',
        ]);

        $activities = ActivityLog::where('entity_type', 'survey_request')
            ->where('entity_id', $survey->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $role = Auth::user()->role->name;

        return view('surveys.show', compact('survey', 'activities', 'role'));
    }

    public function startProcessing(SurveyRequest $survey)
    {
        $survey->update(['status' => SurveyRequest::STATUS_SURVEY_PROCESSING]);

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey->id,
            action: 'SURVEY_PROCESSING_STARTED',
            fromStatus: SurveyRequest::STATUS_SURVEY_REQUESTED,
            toStatus: SurveyRequest::STATUS_SURVEY_PROCESSING,
            remarks: 'Transmission team initiated field feasibility survey',
            userId: Auth::id()
        );

        return back()->with('success', 'Survey status updated to SURVEY_PROCESSING.');
    }

    public function storeFeedback(Request $request, SurveyRequest $survey)
    {
        $validated = $request->validate([
            // Primary NTTN Provider 1
            'provider' => ['required', 'string', 'max:120'],
            'feasible' => ['required', 'boolean'],
            'route' => ['nullable', 'string'],
            'fiber_distance_km' => ['nullable', 'numeric', 'min:0'],
            'nearest_pop' => ['nullable', 'string', 'max:200'],
            'pop_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'pop_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'port' => ['nullable', 'string', 'max:100'],
            'vlan' => ['nullable', 'string', 'max:100'],
            'installation_cost' => ['nullable', 'numeric', 'min:0'],
            'monthly_cost' => ['nullable', 'numeric', 'min:0'],
            'expected_delivery_days' => ['nullable', 'integer', 'min:1'],
            'feedback' => ['nullable', 'string'],

            // Optional 2nd NTTN Provider
            'has_provider_2' => ['nullable'],
            'provider_2' => ['nullable', 'string', 'max:120'],
            'feasible_2' => ['nullable', 'boolean'],
            'route_2' => ['nullable', 'string'],
            'fiber_distance_km_2' => ['nullable', 'numeric', 'min:0'],
            'nearest_pop_2' => ['nullable', 'string', 'max:200'],
            'pop_latitude_2' => ['nullable', 'numeric', 'between:-90,90'],
            'pop_longitude_2' => ['nullable', 'numeric', 'between:-180,180'],
            'vlan_2' => ['nullable', 'string', 'max:100'],
            'installation_cost_2' => ['nullable', 'numeric', 'min:0'],
            'monthly_cost_2' => ['nullable', 'numeric', 'min:0'],
            'expected_delivery_days_2' => ['nullable', 'integer', 'min:1'],
            'feedback_2' => ['nullable', 'string'],
        ]);

        $existingFeedbacks = $survey->feedbacks()->get();

        $isCarrier1Feasible = (bool) $validated['feasible'];
        $hasProvider2 = ($request->boolean('has_provider_2') || $request->boolean('has_second_nttn')) && ! empty($validated['provider_2']);
        $isCarrier2Feasible = $hasProvider2 && (bool) ($validated['feasible_2'] ?? false);
        $isFeasible = $isCarrier1Feasible || $isCarrier2Feasible;

        // 1. Save Provider 1 (Infeasible links are never marked as selected)
        $data1 = [
            'survey_request_id' => $survey->id,
            'provider' => $validated['provider'],
            'feasible' => $isCarrier1Feasible,
            'route' => $validated['route'] ?? null,
            'fiber_distance_km' => $validated['fiber_distance_km'] ?? 0,
            'nearest_pop' => $validated['nearest_pop'] ?? 'N/A',
            'pop_latitude' => $validated['pop_latitude'] ?? null,
            'pop_longitude' => $validated['pop_longitude'] ?? null,
            'port' => $validated['port'] ?? null,
            'vlan' => $validated['vlan'] ?? null,
            'installation_cost' => $validated['installation_cost'] ?? 0,
            'monthly_cost' => $validated['monthly_cost'] ?? 0,
            'expected_delivery_days' => $validated['expected_delivery_days'] ?? 7,
            'feedback' => $validated['feedback'] ?? null,
            'is_selected' => $isCarrier1Feasible,
            'created_by' => Auth::id(),
        ];

        if ($first = $existingFeedbacks->get(0)) {
            $first->update($data1);
        } else {
            SurveyFeedback::create($data1);
        }

        // 2. Save Provider 2 if supplied
        $providersText = $validated['provider'];
        if ($hasProvider2) {
            $data2 = [
                'survey_request_id' => $survey->id,
                'provider' => $validated['provider_2'],
                'feasible' => $isCarrier2Feasible,
                'route' => $validated['route_2'] ?? null,
                'fiber_distance_km' => $validated['fiber_distance_km_2'] ?? 0,
                'nearest_pop' => $validated['nearest_pop_2'] ?? 'POP 2',
                'pop_latitude' => $validated['pop_latitude_2'] ?? null,
                'pop_longitude' => $validated['pop_longitude_2'] ?? null,
                'port' => null,
                'vlan' => $validated['vlan_2'] ?? null,
                'installation_cost' => $validated['installation_cost_2'] ?? 0,
                'monthly_cost' => $validated['monthly_cost_2'] ?? 0,
                'expected_delivery_days' => $validated['expected_delivery_days_2'] ?? 7,
                'feedback' => $validated['feedback_2'] ?? null,
                'is_selected' => (! $isCarrier1Feasible && $isCarrier2Feasible),
                'created_by' => Auth::id(),
            ];

            if ($second = $existingFeedbacks->get(1)) {
                $second->update($data2);
            } else {
                SurveyFeedback::create($data2);
            }
            $providersText .= ' & '.$validated['provider_2'];
        } elseif ($existingFeedbacks->count() > 1 && ! $hasProvider2) {
            $existingFeedbacks->slice(1)->each->delete();
        }

        $isCarrier1Feasible = (bool) $validated['feasible'];
        $isCarrier2Feasible = $hasProvider2 && (bool) ($validated['feasible_2'] ?? false);
        $isFeasible = $isCarrier1Feasible || $isCarrier2Feasible;

        $oldStatus = $survey->status;

        if (! $isFeasible) {
            $newStatus = SurveyRequest::STATUS_NOT_FEASIBLE;
            $survey->update(['status' => $newStatus]);

            ActivityLog::record(
                entityType: 'survey_request',
                entityId: $survey->id,
                action: 'SURVEY_NOT_FEASIBLE',
                fromStatus: $oldStatus,
                toStatus: $newStatus,
                remarks: "Feasibility survey marked NOT FEASIBLE for {$providersText}. Returned to Reseller for revision or cancellation.",
                userId: Auth::id()
            );

            NotificationService::surveyNotFeasible($survey->id, $survey->request_no, $survey->reseller_id);

            return redirect()->route('surveys.show', $survey)
                ->with('warning', "Feasibility feedback recorded as NOT FEASIBLE for {$providersText}. Request returned to Reseller portal.");
        }

        $newStatus = SurveyRequest::STATUS_MD_APPROVAL_PENDING;

        $survey->update(['status' => $newStatus]);

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey->id,
            action: 'FEEDBACK_SUBMITTED',
            fromStatus: $oldStatus,
            toStatus: $newStatus,
            remarks: "Feasibility survey submitted for {$providersText}",
            userId: Auth::id()
        );

        NotificationService::surveyReadyForApproval($survey->id, $survey->request_no);

        return redirect()->route('surveys.show', $survey)
            ->with('success', "Feasibility feedback recorded for {$providersText}. Order escalated for MD Approval.");
    }

    public function edit(SurveyRequest $survey)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && (! $user->isReseller() || $user->reseller?->id !== $survey->reseller_id)) {
            abort(403, 'Unauthorized access to survey request.');
        }

        if (! in_array($survey->status, [SurveyRequest::STATUS_NOT_FEASIBLE, SurveyRequest::STATUS_SURVEY_REQUESTED])) {
            return redirect()->route('surveys.show', $survey)
                ->with('error', 'Only survey requests marked as Not Feasible or Pending can be edited.');
        }

        $reseller = $survey->reseller;
        $customers = $reseller ? $reseller->customers : Customer::all();
        $survey->load(['customer', 'feedbacks']);

        return view('surveys.edit', compact('survey', 'customers', 'reseller'));
    }

    public function update(Request $request, SurveyRequest $survey)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && (! $user->isReseller() || $user->reseller?->id !== $survey->reseller_id)) {
            abort(403, 'Unauthorized to update this survey request.');
        }

        if (! in_array($survey->status, [SurveyRequest::STATUS_NOT_FEASIBLE, SurveyRequest::STATUS_SURVEY_REQUESTED])) {
            return redirect()->route('surveys.show', $survey)
                ->with('error', 'Only survey requests marked as Not Feasible or Pending can be edited.');
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:200'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_address' => ['nullable', 'string'],
            'district' => ['nullable', 'string', 'max:100'],
            'thana' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'bandwidth_mbps' => ['required', 'integer', 'min:1'],
            'service_type' => ['required', 'string', 'max:80'],
            'preferred_nttn' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
        ]);

        if ($survey->customer) {
            $survey->customer->update([
                'name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'] ?? null,
                'address' => $validated['customer_address'] ?? null,
                'district' => $validated['district'] ?? null,
                'thana' => $validated['thana'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ]);
        }

        $oldStatus = $survey->status;
        $survey->update([
            'bandwidth_mbps' => $validated['bandwidth_mbps'],
            'service_type' => $validated['service_type'],
            'preferred_nttn' => $validated['preferred_nttn'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => SurveyRequest::STATUS_SURVEY_REQUESTED,
        ]);

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey->id,
            action: 'SURVEY_RESUBMITTED',
            fromStatus: $oldStatus,
            toStatus: SurveyRequest::STATUS_SURVEY_REQUESTED,
            remarks: "Survey revised and resubmitted by Reseller ({$validated['bandwidth_mbps']} Mbps, {$validated['preferred_nttn']})",
            userId: $user->id
        );

        NotificationService::surveyRequested($survey->id, $survey->request_no, $survey->customer?->name ?? 'Customer');

        return redirect()->route('surveys.show', $survey)
            ->with('success', "Survey request {$survey->request_no} has been revised and resubmitted for feasibility survey.");
    }

    public function cancel(Request $request, SurveyRequest $survey)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && (! $user->isReseller() || $user->reseller?->id !== $survey->reseller_id)) {
            abort(403, 'Unauthorized to cancel this survey request.');
        }

        if (! in_array($survey->status, [SurveyRequest::STATUS_NOT_FEASIBLE, SurveyRequest::STATUS_SURVEY_REQUESTED, SurveyRequest::STATUS_SURVEY_PROCESSING])) {
            return redirect()->route('surveys.show', $survey)
                ->with('error', 'Only pending or infeasible survey requests can be cancelled.');
        }

        $reason = $request->input('reason') ?: 'Cancelled by Reseller after feasibility assessment.';

        $oldStatus = $survey->status;
        $survey->update(['status' => SurveyRequest::STATUS_CANCELLED]);

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey->id,
            action: 'SURVEY_CANCELLED',
            fromStatus: $oldStatus,
            toStatus: SurveyRequest::STATUS_CANCELLED,
            remarks: "Survey request cancelled by Reseller. Note: {$reason}",
            userId: $user->id
        );

        return redirect()->route('surveys.show', $survey)
            ->with('info', "Survey request {$survey->request_no} has been cancelled.");
    }
}
