<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Role;
use App\Models\SurveyFeedback;
use App\Models\SurveyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SurveyController extends Controller
{
    public function index(Request $request)
    {
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
        }

        $requestNo = 'SRV-' . date('Y') . '-' . strtoupper(Str::random(6));

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
        $survey->load([
            'customer',
            'reseller.user',
            'feedback.creator',
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
            'provider' => ['required', 'string', 'max:120'],
            'feasible' => ['required', 'boolean'],
            'route' => ['nullable', 'string'],
            'fiber_distance_km' => ['required', 'numeric', 'min:0'],
            'nearest_pop' => ['required', 'string', 'max:200'],
            'port' => ['nullable', 'string', 'max:100'],
            'vlan' => ['nullable', 'string', 'max:100'],
            'installation_cost' => ['required', 'numeric', 'min:0'],
            'monthly_cost' => ['required', 'numeric', 'min:0'],
            'expected_delivery_days' => ['required', 'integer', 'min:1'],
            'feedback' => ['nullable', 'string'],
        ]);

        SurveyFeedback::updateOrCreate(
            ['survey_request_id' => $survey->id],
            array_merge($validated, ['created_by' => Auth::id()])
        );

        $oldStatus = $survey->status;
        $newStatus = SurveyRequest::STATUS_MD_APPROVAL_PENDING;

        $survey->update(['status' => $newStatus]);

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey->id,
            action: 'FEEDBACK_SUBMITTED',
            fromStatus: $oldStatus,
            toStatus: $newStatus,
            remarks: "Feasibility submitted by Transmission (" . ($validated['feasible'] ? 'Feasible' : 'Not Feasible') . ", " . $validated['fiber_distance_km'] . " km)",
            userId: Auth::id()
        );

        return redirect()->route('surveys.show', $survey)
            ->with('success', 'Feasibility feedback recorded. Order escalated for MD Approval.');
    }
}
