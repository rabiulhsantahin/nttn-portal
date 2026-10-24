@extends('layouts.app', ['title' => 'Survey ' . $survey->request_no, 'header' => 'Survey & Provisioning Workflow: ' . $survey->request_no])

@section('content')
<div class="space-y-8">

    <!-- Top Status Bar & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-extrabold text-white font-mono">{{ $survey->request_no }}</h2>
                <span class="px-3 py-1 rounded-full text-xs font-mono font-bold
                    @if($survey->status === 'ACTIVE') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                    @elseif(str_contains($survey->status, 'MD_APPROVAL')) bg-amber-500/10 text-amber-400 border border-amber-500/20
                    @elseif(str_contains($survey->status, 'REJECTED')) bg-rose-500/10 text-rose-400 border border-rose-500/20
                    @elseif($survey->status === 'NOT_FEASIBLE') bg-rose-500/10 text-rose-400 border border-rose-500/20
                    @elseif($survey->status === 'CANCELLED') bg-slate-500/10 text-slate-400 border border-slate-500/20
                    @elseif(str_contains($survey->status, 'WORK')) bg-cyan-500/10 text-cyan-400 border border-cyan-500/20
                    @elseif(str_contains($survey->status, 'NOC')) bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                    @elseif(str_contains($survey->status, 'BILLING')) bg-violet-500/10 text-violet-400 border border-violet-500/20
                    @else bg-blue-500/10 text-blue-400 border border-blue-500/20 @endif">
                    {{ $survey->status }}
                </span>
            </div>
            <div class="text-xs text-slate-400 mt-1">
                Lodge Date: {{ $survey->created_at->format('M d, Y - H:i') }} | Reseller: <strong class="text-slate-200">{{ $survey->reseller->company_name }}</strong>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if(auth()->user()->isSuperAdmin())
                <form method="POST" action="{{ route('surveys.destroy', $survey) }}" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete survey {{ $survey->request_no }}? All associated feasibility records, work orders, and logs will be removed.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3.5 py-2 bg-rose-600/10 hover:bg-rose-600 text-rose-400 hover:text-white border border-rose-500/20 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Delete Survey</span>
                    </button>
                </form>
            @endif
            @if($survey->workOrder && $survey->workOrder->link)
                <a href="{{ route('links.show', $survey->workOrder->link) }}" class="px-4 py-2 bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 border border-blue-500/30 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/></svg>
                    <span>Circuit: {{ $survey->workOrder->link->link_id }}</span>
                </a>
            @endif
            <a href="{{ route('surveys.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold">
                &larr; All Surveys
            </a>
        </div>
    </div>

    <!-- 12-Stage Visual Progress Stepper -->
    @php
        $stages = [
            'SURVEY_REQUESTED' => '1. Survey Requested',
            'SURVEY_PROCESSING' => '2. Feasibility Survey',
            'FEEDBACK_READY' => '3. Feedback Ready',
            'MD_APPROVAL_PENDING' => '4. MD Capex Review',
            'MD_APPROVED' => '5. Client Work Order',
            'WORK_ORDER_ISSUED' => '6. Field Jointing',
            'NOC_HANDOVER' => '7. NOC Handover',
            'NOC_TESTING' => '8. NOC SLA Tests',
            'BILLING_PENDING' => '9. Commercial Billing',
            'FINAL_MD_APPROVAL' => '10. Final MD Review',
            'ACTIVE' => '11. Active Circuit',
        ];

        $stageKeys = array_keys($stages);
        $effectiveStatus = $survey->status;
        if ($effectiveStatus === 'WORK_IN_PROGRESS') {
            $effectiveStatus = 'WORK_ORDER_ISSUED';
        }
        $currentIndex = array_search($effectiveStatus, $stageKeys);
        if ($currentIndex === false) {
            if ($effectiveStatus === 'NOT_FEASIBLE') {
                $currentIndex = 1;
            } elseif ($effectiveStatus === 'CANCELLED') {
                $currentIndex = 0;
            } else {
                $currentIndex = 3; // Default fallback for rejected/sent back
            }
        }
    @endphp
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 overflow-x-auto shadow-xl">
        <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 font-mono">Service Delivery Lifecycle Stepper</div>
        <div class="flex items-center min-w-[900px] justify-between relative">
            <div class="absolute top-1/2 left-0 right-0 h-0.5 bg-slate-800 -translate-y-1/2 z-0"></div>
            @foreach($stages as $key => $label)
                @php
                    $stepIndex = array_search($key, $stageKeys);
                    $isDone = $stepIndex < $currentIndex || $survey->status === 'ACTIVE';
                    $isCurrent = $key === $survey->status;
                @endphp
                <div class="relative z-10 flex flex-col items-center group">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold font-mono transition-transform
                        @if($isCurrent) bg-blue-500 text-white ring-4 ring-blue-500/20 scale-110 shadow-lg shadow-blue-500/50
                        @elseif($isDone) bg-emerald-500 text-white
                        @else bg-slate-800 text-slate-500 border border-slate-700 @endif">
                        @if($isDone && !$isCurrent)
                            &check;
                        @else
                            {{ $stepIndex + 1 }}
                        @endif
                    </div>
                    <div class="text-[10px] font-semibold mt-2 text-center max-w-[80px]
                        @if($isCurrent) text-blue-400 font-bold
                        @elseif($isDone) text-slate-300
                        @else text-slate-500 @endif">
                        {{ $label }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Main Grid: Left Details + Right Action Workbenches -->
    <div class="grid lg:grid-cols-3 gap-8">
        
        <!-- Left: Static Order Specifications (1 Col) -->
        <div class="space-y-6">
            
            <!-- Customer & Endpoint Card -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-300">Customer & Endpoint</h3>
                    <span class="text-xs text-blue-400 font-mono">Profile</span>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <div class="text-slate-400">Subscriber Name</div>
                        <div class="text-sm font-bold text-white">{{ $survey->customer->name }}</div>
                    </div>
                    <div>
                        <div class="text-slate-400">Physical Address</div>
                        <div class="text-slate-200">{{ $survey->customer->address ?? 'Not specified' }}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <div class="text-slate-400">District</div>
                            <div class="text-slate-200 font-semibold">{{ $survey->customer->district ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-slate-400">Thana / Upazila</div>
                            <div class="text-slate-200 font-semibold">{{ $survey->customer->thana ?? 'N/A' }}</div>
                        </div>
                    </div>
                    @if($survey->customer->latitude && $survey->customer->longitude)
                        <div>
                            <div class="text-slate-400">Geo Coordinates</div>
                            <div class="font-mono text-cyan-400">{{ $survey->customer->latitude }}, {{ $survey->customer->longitude }}</div>
                        </div>
                    @endif
                    <div>
                        <div class="text-slate-400">Contact Phone</div>
                        <div class="text-slate-200">{{ $survey->customer->phone ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <!-- Requested Service Specs -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-300">Technical Request</h3>
                    <span class="text-xs text-cyan-400 font-mono">Parameters</span>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <div class="text-slate-400">Committed Bandwidth</div>
                        <div class="text-2xl font-extrabold text-cyan-400 font-mono">{{ $survey->bandwidth_mbps }} Mbps</div>
                    </div>
                    <div>
                        <div class="text-slate-400">Service Category</div>
                        <div class="text-slate-200 font-semibold">{{ $survey->service_type }}</div>
                    </div>
                    <div>
                        <div class="text-slate-400">Preferred NTTN Provider</div>
                        <div class="text-slate-200">{{ $survey->preferred_nttn ?? 'Any (Best Path)' }}</div>
                    </div>
                    @if($survey->description)
                        <div>
                            <div class="text-slate-400">Client Instructions</div>
                            <div class="p-3 bg-slate-950/60 rounded-xl text-slate-300 italic border border-slate-800/80">{{ $survey->description }}</div>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- Right: Dynamic Action Cards by Stage (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- 1. Transmission Feasibility Feedback Card -->
            @if($survey->feedbacks->isNotEmpty() || $survey->feedback)
                @php
                    $hasWorkOrder = (bool) $survey->workOrder;
                    if ($hasWorkOrder) {
                        // After work order, the infeasible table/card is removed; only the link chosen for the work order proceeds
                        $chosen = ($survey->selectedFeedback && $survey->selectedFeedback->feasible)
                            ? $survey->selectedFeedback
                            : ($survey->feedbacks->firstWhere('feasible', true) ?? $survey->feedback);
                        $allFbs = ($chosen && $chosen->feasible) ? collect([$chosen]) : $survey->feedbacks->where('feasible', true);
                    } else {
                        $allFbs = $survey->feedbacks->isNotEmpty() ? $survey->feedbacks : collect([$survey->feedback]);
                    }
                @endphp
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center text-xs font-bold">&check;</span>
                            <h3 class="font-bold text-white text-base">Transmission Feasibility Report</h3>
                        </div>
                        <span class="text-xs text-slate-400 font-mono">
                            {{ $allFbs->count() > 1 ? 'Dual NTTN Comparison' : ($hasWorkOrder ? 'Approved NTTN Route' : 'Single Provider') }}
                        </span>
                    </div>

                    <div class="grid {{ $allFbs->count() > 1 ? 'md:grid-cols-2 gap-4' : 'grid-cols-1' }}">
                        @foreach($allFbs as $idx => $fb)
                            @if(! $fb->feasible)
                                <div class="p-4 rounded-2xl bg-slate-950/70 border border-rose-500/30 space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                                {{ $fb->provider }}
                                            </span>
                                        </div>
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                            ইনফিজিবল (INFEASIBLE)
                                        </span>
                                    </div>
                                    @if($fb->feedback)
                                        <div class="text-[11px] text-slate-400 italic pt-1">
                                            "{{ $fb->feedback }}"
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="p-4 rounded-2xl bg-slate-950/70 border {{ $fb->is_selected ? 'border-cyan-500/60 shadow-lg shadow-cyan-500/10' : 'border-slate-800' }} space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                                                #{{ $idx + 1 }} {{ $fb->provider }}
                                            </span>
                                            @if($fb->is_selected)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                    ✓ APPROVED
                                                </span>
                                            @endif
                                        </div>
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            FEASIBLE
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-3 gap-2 text-xs">
                                        <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800">
                                            <div class="text-[10px] text-slate-400 uppercase">Fiber Dist</div>
                                            <div class="text-sm font-extrabold text-cyan-400 font-mono mt-0.5">{{ $fb->fiber_distance_km }} km</div>
                                        </div>
                                        <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800">
                                            <div class="text-[10px] text-slate-400 uppercase">Capex</div>
                                            <div class="text-sm font-extrabold text-amber-400 font-mono mt-0.5">৳ {{ number_format($fb->installation_cost, 0) }}</div>
                                        </div>
                                        <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800">
                                            <div class="text-[10px] text-slate-400 uppercase">Monthly</div>
                                            <div class="text-sm font-extrabold text-white font-mono mt-0.5">৳ {{ number_format($fb->monthly_cost, 0) }}</div>
                                        </div>
                                    </div>

                                    <div class="space-y-1.5 text-xs pt-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-400">Nearest POP:</span>
                                            <span class="text-slate-200 font-semibold">{{ $fb->nearest_pop }}</span>
                                        </div>
                                        @if($fb->pop_latitude && $fb->pop_longitude)
                                            <div class="flex items-center justify-between">
                                                <span class="text-slate-400">POP Lat/Long:</span>
                                                <a href="https://www.google.com/maps?q={{ $fb->pop_latitude }},{{ $fb->pop_longitude }}" target="_blank"
                                                    class="font-mono text-cyan-400 hover:text-cyan-300 hover:underline flex items-center gap-1">
                                                    <span>{{ $fb->pop_latitude }}, {{ $fb->pop_longitude }}</span>
                                                    <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                </a>
                                            </div>
                                        @endif
                                        @if($fb->vlan)
                                            <div class="flex items-center justify-between">
                                                <span class="text-slate-400">Assigned VLAN:</span>
                                                <span class="font-mono text-cyan-400">{{ $fb->vlan }}</span>
                                            </div>
                                        @endif
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-400">Delivery SLA:</span>
                                            <span class="font-semibold text-slate-200">{{ $fb->expected_delivery_days }} Days</span>
                                        </div>
                                        @if($fb->route)
                                            <div class="pt-1">
                                                <span class="text-slate-400 block text-[11px]">Optical Route:</span>
                                                <div class="text-slate-300 font-mono text-[11px] p-2 bg-slate-900 rounded-lg border border-slate-800 mt-0.5 truncate" title="{{ $fb->route }}">{{ $fb->route }}</div>
                                            </div>
                                        @endif
                                        @if($fb->feedback)
                                            <div class="text-[11px] text-slate-400 italic pt-1">
                                                "{{ $fb->feedback }}"
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 2. Transmission Team: Input Feasibility Form -->
            @if(in_array($survey->status, ['SURVEY_REQUESTED', 'SURVEY_PROCESSING', 'SENT_BACK']) && (auth()->user()->isTransmission() || auth()->user()->isSuperAdmin()))
                @php
                    $existingFbs = $survey->feedbacks;
                    $fb1 = $existingFbs->get(0) ?? $survey->feedback;
                    $fb2 = $existingFbs->get(1);
                    $hasFb2 = (bool) $fb2;
                @endphp
                <div class="bg-gradient-to-br from-cyan-950/40 via-slate-900 to-slate-900 border border-cyan-500/30 rounded-3xl p-6 shadow-2xl space-y-5">
                    <div class="flex items-center justify-between border-b border-cyan-500/20 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center text-xs font-bold">Tx</span>
                            <h3 class="font-bold text-white text-base">Transmission Feasibility Input</h3>
                        </div>
                        <span class="text-xs text-cyan-400 font-mono">1 or 2 NTTN Carriers</span>
                    </div>

                    @if($survey->status === 'SURVEY_REQUESTED')
                        <form method="POST" action="{{ route('surveys.start-processing', $survey) }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-bold transition-all mb-4">
                                Claim & Mark as SURVEY_PROCESSING
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('surveys.feedback', $survey) }}" class="space-y-6 text-xs">
                        @csrf

                        <!-- NTTN Provider 1 (Primary) -->
                        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                                <span class="font-bold text-cyan-400 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-cyan-500/20 text-cyan-300 flex items-center justify-center text-[10px]">1</span>
                                    NTTN Carrier 1 (Primary)
                                </span>
                                <span class="text-[10px] text-slate-500 font-mono">Required</span>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Carrier Provider *</label>
                                    <input type="text" name="provider" value="{{ old('provider', $fb1?->provider ?? ($survey->preferred_nttn ?: 'Fiber@Home')) }}" required
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Feasibility *</label>
                                    <select name="feasible" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">
                                        <option value="1" {{ old('feasible', $fb1?->feasible ?? 1) == 1 ? 'selected' : '' }}>Feasible (Route Clear)</option>
                                        <option value="0" {{ old('feasible', $fb1?->feasible ?? 1) == 0 ? 'selected' : '' }}>Not Feasible (Duct/Capacity Block)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Fiber Distance (km) *</label>
                                    <input type="number" step="0.001" name="fiber_distance_km" value="{{ old('fiber_distance_km', $fb1?->fiber_distance_km ?? '4.250') }}" required
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Nearest POP Name *</label>
                                    <input type="text" name="nearest_pop" value="{{ old('nearest_pop', $fb1?->nearest_pop ?? 'Central Core POP 02') }}" required
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">POP Latitude (Lat)</label>
                                    <input type="number" step="0.0000001" name="pop_latitude" value="{{ old('pop_latitude', $fb1?->pop_latitude ?? '23.7297430') }}" placeholder="e.g. 23.7297430"
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">POP Longitude (Long)</label>
                                    <input type="number" step="0.0000001" name="pop_longitude" value="{{ old('pop_longitude', $fb1?->pop_longitude ?? '90.4174310') }}" placeholder="e.g. 90.4174310"
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Provisioned VLAN</label>
                                    <input type="text" name="vlan" value="{{ old('vlan', $fb1?->vlan ?? 'VLAN-1420') }}"
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Delivery SLA (Days) *</label>
                                    <input type="number" name="expected_delivery_days" value="{{ old('expected_delivery_days', $fb1?->expected_delivery_days ?? 7) }}" required
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Installation Capex (৳) *</label>
                                    <input type="number" step="0.01" name="installation_cost" value="{{ old('installation_cost', $fb1?->installation_cost ?? '35000') }}" required
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Monthly Cost (৳) *</label>
                                    <input type="number" step="0.01" name="monthly_cost" value="{{ old('monthly_cost', $fb1?->monthly_cost ?? '45000') }}" required
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Detailed Fiber Path</label>
                                    <input type="text" name="route" value="{{ old('route', $fb1?->route ?? 'Primary POP -> Ring Joint 4 -> Customer Premise') }}"
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Technical Feedback Remarks</label>
                                    <textarea name="feedback" rows="2" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">{{ old('feedback', $fb1?->feedback ?? 'Optical budget within -18dBm. Splicing scheduled.') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- 2nd NTTN Provider Toggle -->
                        <div class="pt-1">
                            <label class="inline-flex items-center gap-2 cursor-pointer p-3 rounded-xl bg-slate-950/40 border border-slate-800 hover:border-slate-700 transition-colors">
                                <input type="checkbox" id="has_provider_2_toggle" name="has_provider_2" value="1"
                                    {{ old('has_provider_2', $hasFb2) ? 'checked' : '' }}
                                    onchange="document.getElementById('provider_2_section').classList.toggle('hidden', !this.checked)"
                                    class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-cyan-600 focus:ring-0">
                                <span class="font-bold text-white text-xs">
                                    + Add 2nd NTTN Company Survey (Optional Comparative Feasibility)
                                </span>
                            </label>
                        </div>

                        <!-- NTTN Provider 2 (Alternative / Comparative) -->
                        <div id="provider_2_section" class="{{ old('has_provider_2', $hasFb2) ? '' : 'hidden' }} p-4 rounded-2xl bg-slate-950/70 border border-indigo-500/30 space-y-4">
                            <div class="flex items-center justify-between border-b border-indigo-500/20 pb-2">
                                <span class="font-bold text-indigo-400 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-indigo-500/20 text-indigo-300 flex items-center justify-center text-[10px]">2</span>
                                    NTTN Carrier 2 (Alternative Option)
                                </span>
                                <span class="text-[10px] text-indigo-400/80 font-mono">Comparative</span>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Carrier Provider</label>
                                    <input type="text" name="provider_2" value="{{ old('provider_2', $fb2?->provider ?? '') }}" placeholder="e.g. Summit Communications"
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Feasibility</label>
                                    <select name="feasible_2" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">
                                        <option value="1" {{ old('feasible_2', $fb2?->feasible ?? 1) == 1 ? 'selected' : '' }}>Feasible (Route Clear)</option>
                                        <option value="0" {{ old('feasible_2', $fb2?->feasible ?? 1) == 0 ? 'selected' : '' }}>Not Feasible (Duct/Capacity Block)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Fiber Distance (km)</label>
                                    <input type="number" step="0.001" name="fiber_distance_km_2" value="{{ old('fiber_distance_km_2', $fb2?->fiber_distance_km ?? '3.800') }}"
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Nearest POP Name</label>
                                    <input type="text" name="nearest_pop_2" value="{{ old('nearest_pop_2', $fb2?->nearest_pop ?? 'Tejgaon Central Hub') }}"
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">POP Latitude (Lat)</label>
                                    <input type="number" step="0.0000001" name="pop_latitude_2" value="{{ old('pop_latitude_2', $fb2?->pop_latitude ?? '23.7590000') }}" placeholder="e.g. 23.7590000"
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">POP Longitude (Long)</label>
                                    <input type="number" step="0.0000001" name="pop_longitude_2" value="{{ old('pop_longitude_2', $fb2?->pop_longitude ?? '90.3900000') }}" placeholder="e.g. 90.3900000"
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Provisioned VLAN</label>
                                    <input type="text" name="vlan_2" value="{{ old('vlan_2', $fb2?->vlan ?? 'VLAN-1425') }}"
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Delivery SLA (Days)</label>
                                    <input type="number" name="expected_delivery_days_2" value="{{ old('expected_delivery_days_2', $fb2?->expected_delivery_days ?? 5) }}"
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Installation Capex (৳)</label>
                                    <input type="number" step="0.01" name="installation_cost_2" value="{{ old('installation_cost_2', $fb2?->installation_cost ?? '32000') }}"
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Monthly Cost (৳)</label>
                                    <input type="number" step="0.01" name="monthly_cost_2" value="{{ old('monthly_cost_2', $fb2?->monthly_cost ?? '42000') }}"
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Detailed Fiber Path</label>
                                    <input type="text" name="route_2" value="{{ old('route_2', $fb2?->route ?? 'Tejgaon Node -> Mohakhali Flyover -> Customer Premise') }}"
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Technical Feedback Remarks</label>
                                    <textarea name="feedback_2" rows="2" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">{{ old('feedback_2', $fb2?->feedback ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 text-right">
                            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl shadow-lg transition-all">
                                Submit Feasibility &rarr; Escalate to MD Approval
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- 2.5 Reseller Infeasible Resolution Desk (When NOT_FEASIBLE) -->
            @if($survey->status === 'NOT_FEASIBLE')
                @php
                    $isOwnerOrAdmin = auth()->user()->isSuperAdmin() || (auth()->user()->isReseller() && auth()->user()->reseller?->id === $survey->reseller_id);
                @endphp
                <div class="bg-gradient-to-br from-rose-950/40 via-slate-900 to-slate-900 border border-rose-500/40 rounded-3xl p-6 shadow-2xl space-y-5">
                    <div class="flex items-center justify-between border-b border-rose-500/20 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-rose-500/20 text-rose-400 flex items-center justify-center text-xs font-bold">⚠️</span>
                            <h3 class="font-bold text-white text-base">Transmission Feasibility: Infeasible Route</h3>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-rose-500/10 text-rose-400 border border-rose-500/30">
                            Action Required by Reseller
                        </span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-950/70 border border-rose-500/20 space-y-3 text-xs">
                        <p class="text-slate-300">
                            The transmission engineering team has evaluated this location and determined that the current route is <strong class="text-rose-400 font-bold">Not Feasible</strong> due to duct/capacity block or lack of nearby optical reach.
                        </p>
                        @if($survey->feedbacks->count() > 0)
                            <div class="space-y-2 pt-1">
                                @foreach($survey->feedbacks as $fb)
                                    <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 text-slate-300">
                                        <div class="flex items-center justify-between font-mono font-bold text-xs mb-1">
                                            <span class="text-cyan-400">{{ $fb->provider }}</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-rose-500/20 text-rose-400 border border-rose-500/30 font-bold">NOT FEASIBLE</span>
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            Nearest POP: <span class="text-slate-200 font-semibold">{{ $fb->nearest_pop }}</span> ({{ $fb->fiber_distance_km }} km)
                                        </div>
                                        @if($fb->feedback)
                                            <div class="text-[11px] text-amber-300/90 italic mt-1">
                                                Transmission Remarks: "{{ $fb->feedback }}"
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if($isOwnerOrAdmin)
                        <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
                            <a href="{{ route('surveys.edit', $survey) }}" class="w-full sm:w-auto px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-xl text-xs flex items-center justify-center gap-2 shadow-lg shadow-blue-500/20 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>Edit & Re-submit Survey Parameters</span>
                            </a>

                            <form method="POST" action="{{ route('surveys.cancel', $survey) }}" class="w-full sm:w-auto" onsubmit="return confirm('Are you sure you want to cancel (বাতিল) this survey request?');">
                                @csrf
                                <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 hover:text-rose-300 border border-rose-500/30 font-bold rounded-xl text-xs flex items-center justify-center gap-2 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <span>Cancel Request (বাতিল করুন)</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="text-xs text-slate-400 italic">
                            Awaiting response from Reseller ({{ $survey->reseller->company_name }}) to either revise route parameters or cancel the order.
                        </div>
                    @endif
                </div>
            @endif

            <!-- 2.6 Cancelled Notice -->
            @if($survey->status === 'CANCELLED')
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-3">
                    <div class="flex items-center gap-2 text-slate-400">
                        <span class="w-6 h-6 rounded-lg bg-slate-800 text-slate-400 flex items-center justify-center text-xs font-bold">&times;</span>
                        <h3 class="font-bold text-white text-base">Survey Request Cancelled (বাতিলকৃত)</h3>
                    </div>
                    <p class="text-xs text-slate-400">
                        This survey request was cancelled by the reseller following transmission engineering feasibility evaluation.
                    </p>
                </div>
            @endif

            <!-- 3. MD Approval Panel -->
            @if(in_array($survey->status, ['FEEDBACK_READY', 'MD_APPROVAL_PENDING']) && (auth()->user()->isMd() || auth()->user()->isSuperAdmin()))
                <div class="bg-gradient-to-br from-amber-950/40 via-slate-900 to-slate-900 border border-amber-500/30 rounded-3xl p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between border-b border-amber-500/20 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center text-xs font-bold">MD</span>
                            <h3 class="font-bold text-white text-base">Managing Director Commercial Approval Desk</h3>
                        </div>
                        <span class="text-xs text-amber-400 font-mono font-bold">Review Decision</span>
                    </div>

                    <p class="text-xs text-slate-300 leading-relaxed">
                        Review submitted feasibility survey options below. Approving will select the approved carrier and forward the commercial quotation to the client/reseller portal to issue the official Work Order.
                    </p>

                    <form method="POST" action="{{ route('approvals.decide', $survey) }}" class="space-y-4 text-xs">
                        @csrf

                        @php
                            $feasibleFbs = $survey->feedbacks->where('feasible', true);
                        @endphp

                        @if($feasibleFbs->count() > 1)
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-2">Select Approved NTTN Carrier for Work Order *</label>
                                <div class="grid sm:grid-cols-2 gap-3">
                                    @foreach($feasibleFbs as $fb)
                                        <label class="p-3.5 rounded-xl border {{ $fb->is_selected || $loop->first ? 'border-amber-500 bg-amber-500/10' : 'border-slate-800 bg-slate-950/60' }} cursor-pointer flex items-start gap-3 hover:border-amber-500/50 transition-colors">
                                            <input type="radio" name="selected_feedback_id" value="{{ $fb->id }}" {{ $fb->is_selected || $loop->first ? 'checked' : '' }} class="mt-1 text-amber-500 focus:ring-0">
                                            <div class="space-y-1 text-xs">
                                                <div class="font-bold text-white text-sm">{{ $fb->provider }}</div>
                                                <div class="text-slate-300">Distance: <span class="font-mono text-cyan-400 font-bold">{{ $fb->fiber_distance_km }} km</span> | SLA: <span class="text-white font-bold">{{ $fb->expected_delivery_days }} days</span></div>
                                                <div class="text-slate-300">Capex: <span class="font-mono text-amber-400">৳ {{ number_format($fb->installation_cost, 0) }}</span> | Monthly: <span class="font-mono text-emerald-400">৳ {{ number_format($fb->monthly_cost, 0) }}</span></div>
                                                @if($fb->pop_latitude && $fb->pop_longitude)
                                                    <div class="text-[11px] text-slate-400 font-mono">POP Lat/Long: {{ $fb->pop_latitude }}, {{ $fb->pop_longitude }}</div>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @elseif($feasibleFbs->count() === 1)
                            @php $singleFeasible = $feasibleFbs->first(); @endphp
                            <input type="hidden" name="selected_feedback_id" value="{{ $singleFeasible->id }}">
                            <div class="p-3.5 rounded-xl border border-amber-500/30 bg-slate-950/70 text-xs space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-white text-sm">{{ $singleFeasible->provider }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                        FEASIBLE (একমাত্র ফিজিবল লিংক)
                                    </span>
                                </div>
                                <div class="text-slate-300">Distance: <span class="font-mono text-cyan-400 font-bold">{{ $singleFeasible->fiber_distance_km }} km</span> | SLA: <span class="text-white font-bold">{{ $singleFeasible->expected_delivery_days }} days</span></div>
                                <div class="text-slate-300">Capex: <span class="font-mono text-amber-400">৳ {{ number_format($singleFeasible->installation_cost, 0) }}</span> | Monthly: <span class="font-mono text-emerald-400">৳ {{ number_format($singleFeasible->monthly_cost, 0) }}</span></div>
                            </div>
                        @endif

                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">Executive Remarks / Instructions</label>
                            <textarea name="remarks" rows="2" placeholder="Approved for client work order issuance..."
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white"></textarea>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            <button type="submit" name="decision" value="approve" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center gap-2">
                                <span>&check; Approve & Forward to Client Portal</span>
                            </button>
                            <button type="submit" name="decision" value="send_back" class="px-4 py-2.5 bg-amber-600/20 hover:bg-amber-600/30 text-amber-300 border border-amber-500/30 font-semibold rounded-xl transition-all">
                                Send Back to Transmission
                            </button>
                            <button type="submit" name="decision" value="reject" class="px-4 py-2.5 bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-500/30 font-semibold rounded-xl transition-all">
                                Reject Request
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- 3.5 Client / Reseller Work Order Issuance Desk (When MD_APPROVED) -->
            @if($survey->status === 'MD_APPROVED')
                @php
                    $isInitiator = auth()->user()->isSuperAdmin() || (auth()->user()->isReseller() && auth()->user()->reseller?->id === $survey->reseller_id);
                    $approvedFb = ($survey->selectedFeedback && $survey->selectedFeedback->feasible)
                        ? $survey->selectedFeedback
                        : ($survey->feedbacks->firstWhere('feasible', true) ?? $survey->feedback);
                @endphp

                @if($isInitiator)
                    <div class="bg-gradient-to-br from-emerald-950/40 via-slate-900 to-slate-900 border border-emerald-500/40 rounded-3xl p-6 shadow-2xl space-y-5">
                        <div class="flex items-center justify-between border-b border-emerald-500/20 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold">WO</span>
                                <h3 class="font-bold text-white text-base">Client Work Order Issuance Desk</h3>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                Feasibility Approved by MD
                            </span>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-3">
                            <div class="text-xs font-bold uppercase tracking-wider text-emerald-400">Approved Commercial Quotation</div>
                            <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                                <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-800">
                                    <div class="text-slate-400 text-[11px]">Selected NTTN Provider</div>
                                    <div class="text-white font-bold text-sm mt-0.5">{{ $approvedFb?->provider ?? 'NTTN Carrier' }}</div>
                                </div>
                                <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-800">
                                    <div class="text-slate-400 text-[11px]">Fiber Route Distance</div>
                                    <div class="text-cyan-400 font-mono font-bold text-sm mt-0.5">{{ $approvedFb?->fiber_distance_km ?? 'N/A' }} km</div>
                                </div>
                                <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-800">
                                    <div class="text-slate-400 text-[11px]">Installation Capex (OTC)</div>
                                    <div class="text-amber-400 font-mono font-bold text-sm mt-0.5">৳ {{ number_format($approvedFb?->installation_cost ?? 0, 0) }}</div>
                                </div>
                                <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-800">
                                    <div class="text-slate-400 text-[11px]">Monthly Recurring (MRC)</div>
                                    <div class="text-emerald-400 font-mono font-bold text-sm mt-0.5">৳ {{ number_format($approvedFb?->monthly_cost ?? 0, 0) }}</div>
                                </div>
                            </div>
                            @if($approvedFb?->pop_latitude && $approvedFb?->pop_longitude)
                                <div class="text-xs text-slate-300 flex items-center gap-2 pt-1">
                                    <span class="text-slate-400">Nearest POP Coordinates:</span>
                                    <span class="font-mono text-cyan-400">{{ $approvedFb->pop_latitude }}, {{ $approvedFb->pop_longitude }}</span>
                                    <a href="https://www.google.com/maps?q={{ $approvedFb->pop_latitude }},{{ $approvedFb->pop_longitude }}" target="_blank" class="text-[11px] text-blue-400 hover:underline">
                                        (View on Google Maps &rarr;)
                                    </a>
                                </div>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('surveys.issue-work-order', $survey) }}" class="space-y-4 text-xs">
                            @csrf
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Client Purchase / Work Order Ref No.</label>
                                    <input type="text" name="client_po_no" placeholder="e.g. PO-{{ date('Y') }}-{{ $survey->reseller->reseller_code }}-01 (Optional)"
                                        class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono placeholder:text-slate-600">
                                    <span class="text-[11px] text-slate-500">Leave blank to auto-generate standard Work Order number.</span>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Target Delivery SLA (Days)</label>
                                    <input type="number" name="deadline_days" value="{{ old('deadline_days', $approvedFb?->expected_delivery_days ?? 7) }}" min="1"
                                        class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Work Order Instructions / Scope Remarks</label>
                                    <textarea name="instructions" rows="2" placeholder="Authorized to proceed with fiber laying and POP termination at customer premise..."
                                        class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white"></textarea>
                                </div>
                            </div>

                            <div class="pt-2 text-right">
                                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center gap-2 ml-auto">
                                    <span>&check; Issue Work Order & Authorize Field Jointing</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="bg-gradient-to-br from-amber-950/20 via-slate-900 to-slate-900 border border-amber-500/30 rounded-3xl p-6 shadow-xl space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-sm">⏳</span>
                            <div>
                                <h4 class="text-sm font-bold text-white">Awaiting Client Work Order Confirmation</h4>
                                <p class="text-xs text-slate-300 mt-0.5">
                                    Feasibility has been approved by MD for <strong class="text-emerald-400">{{ $approvedFb?->provider ?? 'selected carrier' }}</strong>.
                                    The quotation is currently sent to the <strong>{{ $survey->reseller->company_name }}</strong> portal.
                                    Once the client issues their Work Order, the Transmission team field jointing & splicing workbench will automatically unlock.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            <!-- 4. Work Order Execution & Transmission Handover to NOC -->
            @if($survey->workOrder)
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center text-xs font-bold">WO</span>
                            <h3 class="font-bold text-white text-base">Work Order: {{ $survey->workOrder->work_order_no }}</h3>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                            {{ $survey->workOrder->status }}
                        </span>
                    </div>

                    <div class="grid sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <span class="text-slate-400">Assigned Engineer:</span>
                            <span class="text-white font-bold ml-1">{{ $survey->workOrder->assignedEngineer->name ?? 'Transmission Team' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400">Target Deadline:</span>
                            <span class="text-cyan-400 font-mono ml-1">{{ $survey->workOrder->deadline?->format('M d, Y') ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400">Instructions:</span>
                            <span class="text-slate-300 ml-1">{{ $survey->workOrder->instructions ?? 'Standard splicing' }}</span>
                        </div>
                    </div>

                    @if($survey->workOrder->status === 'ISSUED' && (auth()->user()->isTransmission() || auth()->user()->isSuperAdmin()))
                        <form method="POST" action="{{ route('work-orders.start', $survey->workOrder) }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-bold">
                                Start On-Field Fiber Splicing &rarr; WORK_IN_PROGRESS
                            </button>
                        </form>
                    @endif

                    @if(in_array($survey->workOrder->status, ['ISSUED', 'WORK_IN_PROGRESS']) && (auth()->user()->isTransmission() || auth()->user()->isSuperAdmin()))
                        <div class="p-4 rounded-2xl bg-slate-950/70 border border-cyan-500/30 mt-4 space-y-4">
                            @if($survey->workOrder->nocHandover?->status === 'REJECTED')
                                <div class="p-4 rounded-2xl bg-rose-950/40 border border-rose-500/40 space-y-2">
                                    <div class="flex items-center gap-2 text-rose-400 font-bold text-xs uppercase tracking-wider">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span>Trouble Ticket Dispatched by NOC — Field Rework Required</span>
                                    </div>
                                    <div class="text-xs text-slate-300">
                                        NOC Defect Reason: <strong class="text-white">{{ $survey->workOrder->nocHandover->remarks }}</strong>
                                    </div>
                                    <div class="text-[11px] text-rose-300">
                                        Please conduct physical field rectification (re-splicing, patch cord replacement, optical level check) and re-submit the handover to NOC below.
                                    </div>
                                </div>
                            @endif

                            <h4 class="text-xs font-bold uppercase tracking-wider text-cyan-400">
                                {{ $survey->workOrder->nocHandover?->status === 'REJECTED' ? 'Re-Submit Completed Physical Link to NOC' : 'Handover Completed Physical Link to NOC' }}
                            </h4>
                            <form method="POST" action="{{ route('work-orders.handover', $survey->workOrder) }}" class="space-y-3 text-xs">
                                @csrf
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-300 uppercase mb-1">Circuit ID *</label>
                                        <input type="text" name="circuit_id" value="{{ old('circuit_id', $survey->workOrder->link?->circuit_id ?? ('CIR-' . strtoupper(Str::random(6)))) }}" required
                                            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-300 uppercase mb-1">NTTN Link ID <span class="text-[10px] text-slate-400 font-normal lowercase">(carrier link id)</span></label>
                                        <input type="text" name="nttn_link_id" value="{{ old('nttn_link_id', $survey->workOrder->link?->nttn_link_id) }}" placeholder="e.g. NTTN-LNK-88219"
                                            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-300 uppercase mb-1">NTTN Carrier Provider *</label>
                                        <input type="text" name="nttn_provider" value="{{ old('nttn_provider', $survey->workOrder->link?->nttn_provider ?? ($survey->selectedFeedback?->provider ?? ($survey->feedback?->provider ?? 'Fiber@Home'))) }}" required
                                            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-300 uppercase mb-1">POP Port *</label>
                                        <input type="text" name="port" value="{{ old('port', $survey->workOrder->link?->port ?? ($survey->selectedFeedback?->port ?? ($survey->feedback?->port ?? 'TenGigE-0/1/4'))) }}" required
                                            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block font-semibold text-slate-300 uppercase mb-1">Provisioned VLAN *</label>
                                        <input type="text" name="vlan" value="{{ old('vlan', $survey->workOrder->link?->vlan ?? ($survey->selectedFeedback?->vlan ?? ($survey->feedback?->vlan ?? 'VLAN-2040'))) }}" required
                                            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block font-semibold text-slate-300 uppercase mb-1">Handover Notes for NOC Team</label>
                                        <input type="text" name="remarks" value="{{ $survey->workOrder->nocHandover?->status === 'REJECTED' ? 'Field rework and re-splicing completed. Optical level tested optimal.' : 'Fiber jointed and patched at ODF tray 4. Optical power at -16.5 dBm.' }}"
                                            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">
                                    </div>
                                </div>
                                <div class="text-right pt-1">
                                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl shadow-lg transition-all">
                                        {{ $survey->workOrder->nocHandover?->status === 'REJECTED' ? 'Re-Submit Field Work & Handover to NOC →' : 'Complete Field Work & Submit NOC Handover →' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endif

            <!-- 5. NOC Verification Desk -->
            @if($survey->workOrder?->nocHandover)
                @php $handover = $survey->workOrder->nocHandover; @endphp
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold">NOC</span>
                            <h3 class="font-bold text-white text-base">Network Operations Center (NOC) Handover</h3>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold
                            @if($handover->status === 'VERIFIED') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                            @elseif($handover->status === 'REJECTED') bg-rose-500/10 text-rose-400 border border-rose-500/20
                            @elseif($handover->status === 'TESTING') bg-amber-500/10 text-amber-400 border border-amber-500/20
                            @else bg-blue-500/10 text-blue-400 border border-blue-500/20 @endif">
                            {{ $handover->status }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">Handed over at: {{ $handover->handed_over_at?->format('M d, Y H:i') }}</span>
                        @if($handover->verified_at)
                            <span class="text-emerald-400 font-semibold">Verified by {{ $handover->verifier->name ?? 'NOC' }} ({{ $handover->verified_at->format('M d, Y H:i') }})</span>
                        @endif
                    </div>

                    @if($handover->link)
                        <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800 flex flex-wrap gap-4 text-xs font-mono text-slate-300">
                            <span>Circuit: <strong class="text-cyan-400">{{ $handover->link->circuit_id ?? 'N/A' }}</strong></span>
                            @if(!auth()->user()->isReseller() && $handover->link->nttn_link_id)
                                <span>NTTN Link ID: <strong class="text-emerald-400">{{ $handover->link->nttn_link_id }}</strong></span>
                            @endif
                            <span>Carrier: <strong class="text-slate-200">{{ $handover->link->nttn_provider ?? 'N/A' }}</strong></span>
                            <span>VLAN: <strong class="text-slate-200">{{ $handover->link->vlan ?? 'N/A' }}</strong></span>
                            <span>Port: <strong class="text-slate-200">{{ $handover->link->port ?? 'N/A' }}</strong></span>
                        </div>
                    @endif

                    @if($handover->status === 'REJECTED')
                        <div class="p-4 rounded-2xl bg-rose-950/30 border border-rose-500/30 space-y-2">
                            <div class="flex items-center gap-2 text-rose-400 font-bold text-xs">
                                <span>⚠️ Handover Returned to Transmission Team</span>
                            </div>
                            <div class="text-xs text-slate-300">
                                Reason: <strong class="text-white">{{ $handover->remarks }}</strong>
                            </div>
                            <div class="text-[11px] text-slate-400">
                                Trouble ticket dispatched. Waiting for Transmission engineers to complete field jointing rework and re-submit to NOC.
                            </div>
                        </div>
                    @endif

                    @if(in_array($handover->status, ['RECEIVED', 'TESTING']) && (auth()->user()->isNoc() || auth()->user()->isSuperAdmin()))
                        <div class="p-4 rounded-2xl bg-emerald-950/30 border border-emerald-500/30 space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-400">8-Point SLA Technical Test Suite</h4>
                                <span class="text-[11px] font-mono text-emerald-300">All checks must pass to verify</span>
                            </div>

                            <form method="POST" action="{{ route('noc.submit-test', $handover) }}" class="space-y-4 text-xs">
                                @csrf
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <label class="flex items-center gap-2 p-2.5 bg-slate-900/80 rounded-xl border border-slate-800 cursor-pointer">
                                        <input type="checkbox" name="physical_link" value="1" checked class="rounded bg-slate-950 text-emerald-500 focus:ring-0">
                                        <span>1. Physical Fiber Continuity</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-2.5 bg-slate-900/80 rounded-xl border border-slate-800 cursor-pointer">
                                        <input type="checkbox" name="optical_power" value="1" checked class="rounded bg-slate-950 text-emerald-500 focus:ring-0">
                                        <span>2. Optical Power (-12 to -22 dBm)</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-2.5 bg-slate-900/80 rounded-xl border border-slate-800 cursor-pointer">
                                        <input type="checkbox" name="vlan_ok" value="1" checked class="rounded bg-slate-950 text-emerald-500 focus:ring-0">
                                        <span>3. VLAN Tagging & Q-in-Q</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-2.5 bg-slate-900/80 rounded-xl border border-slate-800 cursor-pointer">
                                        <input type="checkbox" name="bandwidth_ok" value="1" checked class="rounded bg-slate-950 text-emerald-500 focus:ring-0">
                                        <span>4. Throughput Test ({{ $survey->bandwidth_mbps }} Mbps)</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-2.5 bg-slate-900/80 rounded-xl border border-slate-800 cursor-pointer">
                                        <input type="checkbox" name="ping_ok" value="1" checked class="rounded bg-slate-950 text-emerald-500 focus:ring-0">
                                        <span>5. ICMP Ping Test (0% Loss)</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-2.5 bg-slate-900/80 rounded-xl border border-slate-800 cursor-pointer">
                                        <input type="checkbox" name="latency_ok" value="1" checked class="rounded bg-slate-950 text-emerald-500 focus:ring-0">
                                        <span>6. Round-Trip Latency &lt; 5ms</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-2.5 bg-slate-900/80 rounded-xl border border-slate-800 cursor-pointer">
                                        <input type="checkbox" name="packet_loss_ok" value="1" checked class="rounded bg-slate-950 text-emerald-500 focus:ring-0">
                                        <span>7. Jitter & Frame Stability</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-2.5 bg-slate-900/80 rounded-xl border border-slate-800 cursor-pointer">
                                        <input type="checkbox" name="monitoring_ok" value="1" checked class="rounded bg-slate-950 text-emerald-500 focus:ring-0">
                                        <span>8. NMS SNMP Polling Enrolled</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">NOC Testing Remarks / Defect Details</label>
                                    <input type="text" name="remarks" id="noc_remarks" value="Full optical & throughput validation completed successfully."
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">
                                    <span class="text-[11px] text-slate-400">If returning TT to Transmission, describe the fault (e.g. optical loss, macro-bend, port error).</span>
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                                    <button type="submit" name="action" value="reject" class="px-4 py-2.5 bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/30 font-semibold rounded-xl transition-all flex items-center gap-2" onclick="if(document.getElementById('noc_remarks').value === 'Full optical & throughput validation completed successfully.') { document.getElementById('noc_remarks').value = 'Optical power / SLA failure. Requires field re-splicing.'; }">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span>Issue TT & Send Back to Tx</span>
                                    </button>

                                    <button type="submit" name="action" value="verify" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center gap-2">
                                        <span>&check; Submit NOC Verification &rarr; Transfer to Billing</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endif

            <!-- 6. Billing Department Workbench -->
            @if($survey->status === 'BILLING_PENDING' && (auth()->user()->isBilling() || auth()->user()->isSuperAdmin()))
                @php
                    $existingBilling = $survey->workOrder?->link?->billing;
                    $lastReturnedApproval = $survey->approvals()->where('approval_stage', 'FINAL_MD')->where('status', 'SENT_BACK')->latest()->first();
                @endphp
                <div class="bg-gradient-to-br from-violet-950/40 via-slate-900 to-slate-900 border border-violet-500/30 rounded-3xl p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between border-b border-violet-500/20 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-violet-500/20 text-violet-400 flex items-center justify-center text-xs font-bold">$$</span>
                            <h3 class="font-bold text-white text-base">Commercial Invoicing & Billing Generation</h3>
                        </div>
                        <span class="text-xs font-mono font-bold {{ $lastReturnedApproval ? 'text-amber-400 bg-amber-500/10 px-2.5 py-0.5 rounded-full border border-amber-500/20' : 'text-violet-400' }}">
                            {{ $lastReturnedApproval ? '⚠️ Billing Returned for Correction' : 'Action Required' }}
                        </span>
                    </div>

                    @if($lastReturnedApproval)
                        <div class="p-4 rounded-2xl bg-amber-950/40 border border-amber-500/40 space-y-2">
                            <div class="flex items-center gap-2 text-amber-400 font-bold text-xs uppercase tracking-wider">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Returned by Managing Director (MD) — Billing Correction Requested</span>
                            </div>
                            <div class="text-xs text-slate-300">
                                MD Remarks / Reason: <strong class="text-white">{{ $lastReturnedApproval->remarks }}</strong>
                            </div>
                            <div class="text-[11px] text-slate-400">
                                Please revise the commercial charges, cycle dates, or invoice values and re-submit to MD for final commissioning.
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('billing.process', $survey) }}" class="space-y-4 text-xs">
                        @csrf
                        <div class="grid sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Installation Charge (৳) *</label>
                                <input type="number" step="0.01" name="installation_charge" value="{{ old('installation_charge', $existingBilling->installation_charge ?? ($survey->selectedFeedback?->installation_cost ?? ($survey->feedback?->installation_cost ?? 40000))) }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Monthly Billing (৳) *</label>
                                <input type="number" step="0.01" name="monthly_charge" value="{{ old('monthly_charge', $existingBilling->monthly_charge ?? ($survey->selectedFeedback?->monthly_cost ?? ($survey->feedback?->monthly_cost ?? 60000))) }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Carrier NTTN Cost (৳) *</label>
                                <input type="number" step="0.01" name="nttn_cost" value="{{ old('nttn_cost', $existingBilling->nttn_cost ?? 25000) }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Billing Start Date *</label>
                                <input type="date" name="billing_start_date" value="{{ old('billing_start_date', $existingBilling?->billing_start_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">First Invoice Due Date *</label>
                                <input type="date" name="due_date" value="{{ old('due_date', $existingBilling?->due_date?->format('Y-m-d') ?? date('Y-m-d', strtotime('+15 days'))) }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                        </div>

                        <div class="text-right pt-2">
                            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-bold rounded-xl shadow-lg transition-all">
                                {{ $lastReturnedApproval ? 'Re-Issue Corrected Invoice → Escalate to Final MD Commissioning' : 'Issue Commercial Invoice → Escalate to Final MD Commissioning' }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- 7. MD Final Commissioning Approval -->
            @if($survey->status === 'FINAL_MD_APPROVAL' && (auth()->user()->isMd() || auth()->user()->isSuperAdmin()))
                <div class="bg-gradient-to-br from-emerald-950/40 via-slate-900 to-slate-900 border border-emerald-500/40 rounded-3xl p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between border-b border-emerald-500/20 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold">&star;</span>
                            <h3 class="font-bold text-white text-base">Final Commissioning Sign-Off (Managing Director)</h3>
                        </div>
                        <span class="text-xs text-emerald-400 font-mono font-bold">Ready to Activate</span>
                    </div>

                    <p class="text-xs text-slate-300 leading-relaxed">
                        Technical SLA verification has passed, and initial commercial invoicing has been generated. Review the billing and circuit particulars below. If everything is verified, authorize live service; or if there is any billing discrepancy, return to the Billing department for correction.
                    </p>

                    @if($survey->workOrder?->link?->billing)
                        @php $b = $survey->workOrder->link->billing; @endphp
                        <div class="p-4 bg-slate-950/70 border border-slate-800 rounded-2xl grid sm:grid-cols-4 gap-3 text-xs font-mono">
                            <div>
                                <div class="text-[10px] text-slate-400 uppercase">Commercial Invoice</div>
                                <div class="text-violet-400 font-bold">{{ $b->invoice_no }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] text-slate-400 uppercase">Installation Charge</div>
                                <div class="text-white font-bold">৳ {{ number_format($b->installation_charge, 2) }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] text-slate-400 uppercase">Monthly Rate / NTTN Cost</div>
                                <div class="text-emerald-400 font-bold">৳ {{ number_format($b->monthly_charge, 2) }} <span class="text-[10px] text-slate-400 font-normal">/ ৳ {{ number_format($b->nttn_cost, 2) }}</span></div>
                            </div>
                            <div>
                                <div class="text-[10px] text-slate-400 uppercase">Cycle Start & Due Date</div>
                                <div class="text-slate-300">{{ $b->billing_start_date?->format('M d') }} &rarr; {{ $b->due_date?->format('M d, Y') }}</div>
                            </div>
                        </div>
                    @endif

                    <div class="grid md:grid-cols-2 gap-4 pt-2">
                        <!-- Final Approval Form -->
                        <div class="p-4 bg-emerald-950/30 border border-emerald-500/30 rounded-2xl space-y-3">
                            <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Authorize & Go Live</span>
                            </h4>
                            <form method="POST" action="{{ route('approvals.final-commission', $survey) }}" class="space-y-3 text-xs">
                                @csrf
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Sign-off Remarks</label>
                                    <input type="text" name="remarks" value="Commercial & technical conditions satisfied. Authorized for live service."
                                        class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white">
                                </div>
                                <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Sign Off & Launch Live Circuit</span>
                                </button>
                            </form>
                        </div>

                        <!-- Return to Billing Department Form -->
                        <div class="p-4 bg-rose-950/20 border border-amber-500/30 rounded-2xl space-y-3">
                            <h4 class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Billing Problem? Return for Correction</span>
                            </h4>
                            <form method="POST" action="{{ route('approvals.return-billing', $survey) }}" class="space-y-3 text-xs">
                                @csrf
                                <div>
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">Reason / Notes for Billing Dept *</label>
                                    <input type="text" name="remarks" required placeholder="e.g. Monthly rate mismatch, please correct invoice..."
                                        class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white">
                                </div>
                                <button type="submit" class="w-full py-2.5 bg-amber-600 hover:bg-amber-500 text-white font-bold rounded-xl shadow-lg shadow-amber-600/30 transition-all flex items-center justify-center gap-2" onclick="return confirm('Return this circuit to Billing Department for correction?');">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                    <span>Return to Billing Department &rarr;</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <!-- 8. Live Active Status Card -->
            @if($survey->status === 'ACTIVE' && $survey->workOrder?->link)
                <div class="bg-gradient-to-br from-emerald-950/40 via-slate-900 to-slate-900 border border-emerald-500/40 rounded-3xl p-6 shadow-2xl flex items-center justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 text-xs font-mono text-emerald-400 font-bold mb-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                            ACTIVE LIVE CIRCUIT IN PRODUCTION
                        </div>
                        <h3 class="text-xl font-extrabold text-white font-mono">{{ $survey->workOrder->link->link_id }}</h3>
                        <div class="text-xs text-slate-400 mt-1">Circuit ID: {{ $survey->workOrder->link->circuit_id }}@if(!auth()->user()->isReseller() && $survey->workOrder->link->nttn_link_id) | NTTN Link ID: <span class="text-cyan-400 font-mono font-bold">{{ $survey->workOrder->link->nttn_link_id }}</span>@endif | Bandwidth: {{ $survey->workOrder->link->bandwidth_mbps }} Mbps</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('links.chat', $survey->workOrder->link) }}" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <span>Live NOC Chat</span>
                        </a>
                        <a href="{{ route('links.show', $survey->workOrder->link) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition-all">
                            Circuit Dashboard &rarr;
                        </a>
                    </div>
                </div>
            @endif

            <!-- Chronological Audit Trail for this Survey -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                    <h3 class="font-bold text-white text-base">Immutable Lifecycle Audit Trail</h3>
                    <span class="text-xs text-slate-400 font-mono">{{ $activities->count() }} Recorded Transitions</span>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @forelse($activities as $act)
                            <div class="flex items-start gap-4 text-xs">
                                <div class="w-2 h-2 rounded-full bg-blue-400 mt-1.5 flex-shrink-0"></div>
                                <div class="flex-1 bg-slate-950/50 border border-slate-800/80 rounded-2xl p-4">
                                    <div class="flex items-center justify-between text-slate-400 mb-1">
                                        <span class="font-mono font-bold text-blue-400">{{ $act->action }}</span>
                                        <span class="font-mono text-[11px] text-slate-500">{{ $act->created_at?->format('M d, Y H:i:s') }}</span>
                                    </div>
                                    <div class="text-slate-200 text-sm font-medium">{{ $act->remarks }}</div>
                                    <div class="flex items-center gap-3 text-[11px] text-slate-400 mt-2 font-mono">
                                        <span>User: <strong class="text-slate-300">{{ $act->user?->name ?? 'System' }}</strong></span>
                                        @if($act->from_status || $act->to_status)
                                            <span>({{ $act->from_status ?? 'START' }} &rarr; <span class="text-cyan-400">{{ $act->to_status }}</span>)</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-slate-500 text-center py-4 text-xs">No audit entries found.</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
