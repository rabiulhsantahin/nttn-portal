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
            'WORK_ORDER_ISSUED' => '5. Work Order Issued',
            'WORK_IN_PROGRESS' => '6. Field Splicing',
            'NOC_HANDOVER' => '7. NOC Handover',
            'NOC_TESTING' => '8. NOC SLA Tests',
            'BILLING_PENDING' => '9. Commercial Billing',
            'FINAL_MD_APPROVAL' => '10. Final MD Review',
            'ACTIVE' => '11. Active Circuit',
        ];

        $stageKeys = array_keys($stages);
        $currentIndex = array_search($survey->status, $stageKeys);
        if ($currentIndex === false) {
            $currentIndex = 3; // Default fallback for rejected/sent back
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
            @if($survey->feedback)
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center text-xs font-bold">&check;</span>
                            <h3 class="font-bold text-white text-base">Transmission Feasibility Report</h3>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold {{ $survey->feedback->feasible ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                            {{ $survey->feedback->feasible ? 'FEASIBLE' : 'INFEASIBLE' }}
                        </span>
                    </div>

                    <div class="grid sm:grid-cols-3 gap-4 text-xs">
                        <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800">
                            <div class="text-slate-400">Fiber Distance</div>
                            <div class="text-base font-extrabold text-cyan-400 font-mono mt-0.5">{{ $survey->feedback->fiber_distance_km }} km</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800">
                            <div class="text-slate-400">Installation Capex</div>
                            <div class="text-base font-extrabold text-amber-400 font-mono mt-0.5">৳ {{ number_format($survey->feedback->installation_cost, 2) }}</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800">
                            <div class="text-slate-400">Recurring Monthly Cost</div>
                            <div class="text-base font-extrabold text-white font-mono mt-0.5">৳ {{ number_format($survey->feedback->monthly_cost, 2) }}</div>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4 text-xs pt-2">
                        <div>
                            <span class="text-slate-400">Nearest Transmission POP:</span>
                            <span class="text-slate-200 font-semibold ml-1">{{ $survey->feedback->nearest_pop }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400">Carrier Provider:</span>
                            <span class="text-slate-200 font-semibold ml-1">{{ $survey->feedback->provider }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400">Port / Interface:</span>
                            <span class="font-mono text-cyan-400 ml-1">{{ $survey->feedback->port ?? 'TBD' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400">Assigned VLAN:</span>
                            <span class="font-mono text-cyan-400 ml-1">{{ $survey->feedback->vlan ?? 'TBD' }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="text-slate-400">Optical Route:</span>
                            <div class="text-slate-300 font-mono text-[11px] mt-1 p-2 bg-slate-950 rounded-lg border border-slate-800">{{ $survey->feedback->route }}</div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- 2. Transmission Team: Input Feasibility Form -->
            @if(in_array($survey->status, ['SURVEY_REQUESTED', 'SURVEY_PROCESSING', 'SENT_BACK']) && (auth()->user()->isTransmission() || auth()->user()->isSuperAdmin()))
                <div class="bg-gradient-to-br from-cyan-950/40 via-slate-900 to-slate-900 border border-cyan-500/30 rounded-3xl p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between border-b border-cyan-500/20 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center text-xs font-bold">Tx</span>
                            <h3 class="font-bold text-white text-base">Transmission Feasibility Input</h3>
                        </div>
                        <span class="text-xs text-cyan-400 font-mono">Action Required</span>
                    </div>

                    @if($survey->status === 'SURVEY_REQUESTED')
                        <form method="POST" action="{{ route('surveys.start-processing', $survey) }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-bold transition-all mb-4">
                                Claim & Mark as SURVEY_PROCESSING
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('surveys.feedback', $survey) }}" class="space-y-4 text-xs">
                        @csrf
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Carrier Provider *</label>
                                <input type="text" name="provider" value="{{ old('provider', $survey->preferred_nttn ?? 'Fiber@Home') }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Feasibility *</label>
                                <select name="feasible" required class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white">
                                    <option value="1">Feasible (Route Clear)</option>
                                    <option value="0">Not Feasible (Duct/Capacity Block)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Fiber Distance (km) *</label>
                                <input type="number" step="0.001" name="fiber_distance_km" value="{{ old('fiber_distance_km', '4.250') }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Nearest POP *</label>
                                <input type="text" name="nearest_pop" value="{{ old('nearest_pop', 'Central Core POP 02') }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">POP Port / Interface</label>
                                <input type="text" name="port" value="{{ old('port', 'TenGigE-0/0/2') }}"
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Provisioned VLAN</label>
                                <input type="text" name="vlan" value="{{ old('vlan', 'VLAN-1420') }}"
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Installation Capex (৳) *</label>
                                <input type="number" step="0.01" name="installation_cost" value="{{ old('installation_cost', '35000') }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Monthly Cost (৳) *</label>
                                <input type="number" step="0.01" name="monthly_cost" value="{{ old('monthly_cost', '45000') }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Delivery SLA (Days) *</label>
                                <input type="number" name="expected_delivery_days" value="{{ old('expected_delivery_days', 7) }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Detailed Fiber Path</label>
                                <input type="text" name="route" value="{{ old('route', 'Primary POP -> Ring Joint 4 -> Customer Premise') }}"
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Technical Feedback Remarks</label>
                                <textarea name="feedback" rows="2" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white">{{ old('feedback', 'Optical budget within -18dBm. Splicing scheduled.') }}</textarea>
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
                        Review Capex (৳ {{ number_format($survey->feedback?->installation_cost ?? 0, 2) }}) and Monthly Revenue/Cost (৳ {{ number_format($survey->feedback?->monthly_cost ?? 0, 2) }}). Approving this will instantly issue a binding Work Order to Transmission engineers.
                    </p>

                    <form method="POST" action="{{ route('approvals.decide', $survey) }}" class="space-y-4 text-xs">
                        @csrf
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">Executive Remarks / Instructions</label>
                            <textarea name="remarks" rows="2" placeholder="Approved for deployment within 7 days SLA..."
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white"></textarea>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            <button type="submit" name="decision" value="approve" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center gap-2">
                                <span>&check; Approve & Issue Work Order</span>
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
                        <div class="p-4 rounded-2xl bg-slate-950/70 border border-cyan-500/30 mt-4 space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-cyan-400">Handover Completed Physical Link to NOC</h4>
                            <form method="POST" action="{{ route('work-orders.handover', $survey->workOrder) }}" class="space-y-3 text-xs">
                                @csrf
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-300 uppercase mb-1">Circuit ID *</label>
                                        <input type="text" name="circuit_id" value="{{ old('circuit_id', 'CIR-' . strtoupper(Str::random(6))) }}" required
                                            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-300 uppercase mb-1">NTTN Carrier Provider *</label>
                                        <input type="text" name="nttn_provider" value="{{ old('nttn_provider', $survey->feedback->provider ?? 'Fiber@Home') }}" required
                                            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-300 uppercase mb-1">Provisioned VLAN *</label>
                                        <input type="text" name="vlan" value="{{ old('vlan', $survey->feedback->vlan ?? 'VLAN-2040') }}" required
                                            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-300 uppercase mb-1">POP Port *</label>
                                        <input type="text" name="port" value="{{ old('port', $survey->feedback->port ?? 'TenGigE-0/1/4') }}" required
                                            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white font-mono">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block font-semibold text-slate-300 uppercase mb-1">Handover Notes for NOC Team</label>
                                        <input type="text" name="remarks" value="Fiber jointed and patched at ODF tray 4. Optical power at -16.5 dBm."
                                            class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">
                                    </div>
                                </div>
                                <div class="text-right pt-1">
                                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl shadow-lg transition-all">
                                        Complete Field Work & Submit NOC Handover &rarr;
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
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            {{ $handover->status }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">Handed over at: {{ $handover->handed_over_at?->format('M d, Y H:i') }}</span>
                        @if($handover->verified_at)
                            <span class="text-emerald-400 font-semibold">Verified by {{ $handover->verifier->name ?? 'NOC' }} ({{ $handover->verified_at->format('M d, Y H:i') }})</span>
                        @endif
                    </div>

                    @if(in_array($handover->status, ['RECEIVED', 'TESTING']) && (auth()->user()->isNoc() || auth()->user()->isSuperAdmin()))
                        <div class="p-4 rounded-2xl bg-emerald-950/30 border border-emerald-500/30 space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-400">8-Point SLA Technical Test Suite</h4>
                                <span class="text-[11px] font-mono text-emerald-300">All checks must pass</span>
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
                                    <label class="block font-semibold text-slate-300 uppercase mb-1">NOC Testing Remarks</label>
                                    <input type="text" name="remarks" value="Full optical & throughput validation completed successfully."
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">
                                </div>

                                <div class="text-right">
                                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition-all">
                                        Submit NOC Verification &rarr; Transfer to Billing
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endif

            <!-- 6. Billing Department Workbench -->
            @if($survey->status === 'BILLING_PENDING' && (auth()->user()->isBilling() || auth()->user()->isSuperAdmin()))
                <div class="bg-gradient-to-br from-violet-950/40 via-slate-900 to-slate-900 border border-violet-500/30 rounded-3xl p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between border-b border-violet-500/20 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-violet-500/20 text-violet-400 flex items-center justify-center text-xs font-bold">$$</span>
                            <h3 class="font-bold text-white text-base">Commercial Invoicing & Billing Generation</h3>
                        </div>
                        <span class="text-xs text-violet-400 font-mono font-bold">Action Required</span>
                    </div>

                    <form method="POST" action="{{ route('billing.process', $survey) }}" class="space-y-4 text-xs">
                        @csrf
                        <div class="grid sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Installation Charge (৳) *</label>
                                <input type="number" step="0.01" name="installation_charge" value="{{ old('installation_charge', $survey->feedback->installation_cost ?? 40000) }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Monthly Billing (৳) *</label>
                                <input type="number" step="0.01" name="monthly_charge" value="{{ old('monthly_charge', $survey->feedback->monthly_cost ?? 60000) }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Carrier NTTN Cost (৳) *</label>
                                <input type="number" step="0.01" name="nttn_cost" value="{{ old('nttn_cost', 25000) }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Billing Start Date *</label>
                                <input type="date" name="billing_start_date" value="{{ date('Y-m-d') }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">First Invoice Due Date *</label>
                                <input type="date" name="due_date" value="{{ date('Y-m-d', strtotime('+15 days')) }}" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                        </div>

                        <div class="text-right pt-2">
                            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-bold rounded-xl shadow-lg transition-all">
                                Issue Commercial Invoice &rarr; Escalate to Final MD Commissioning
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
                        Technical SLA verification has passed, and initial commercial invoicing has been cleared. Signing off will promote this circuit to <strong>ACTIVE</strong> production status and create the dedicated NOC Real-time Chat Room.
                    </p>

                    <form method="POST" action="{{ route('approvals.final-commission', $survey) }}" class="space-y-3 text-xs">
                        @csrf
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">Commissioning Sign-off Remarks</label>
                            <input type="text" name="remarks" value="Commercial & technical conditions satisfied. Authorized for live service."
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white">
                        </div>
                        <div class="text-right">
                            <button type="submit" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold rounded-xl shadow-xl shadow-emerald-600/40 transition-all flex items-center gap-2 ml-auto">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Sign Off Final Approval & Launch Live Circuit</span>
                            </button>
                        </div>
                    </form>
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
                        <div class="text-xs text-slate-400 mt-1">Circuit ID: {{ $survey->workOrder->link->circuit_id }} | Bandwidth: {{ $survey->workOrder->link->bandwidth_mbps }} Mbps</div>
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
