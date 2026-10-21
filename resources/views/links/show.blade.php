@extends('layouts.app', ['title' => 'Circuit ' . $link->link_id, 'header' => 'Optical Circuit Command: ' . $link->link_id])

@section('content')
<div class="space-y-8">

    <!-- Header Circuit Banner -->
    <div class="bg-gradient-to-r from-blue-950/60 via-slate-900 to-slate-900 border border-blue-500/30 rounded-3xl p-8 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-3xl font-extrabold text-white font-mono">{{ $link->link_id }}</h2>
                <span class="px-3 py-1 rounded-full text-xs font-mono font-bold
                    @if($link->status === 'ACTIVE') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                    @elseif($link->status === 'SUSPENDED') bg-rose-500/10 text-rose-400 border border-rose-500/20
                    @else bg-blue-500/10 text-blue-400 border border-blue-500/20 @endif">
                    {{ $link->status }}
                </span>
            </div>
            <div class="text-xs text-slate-300 mt-2 font-mono flex flex-wrap items-center gap-4">
                <span>Circuit ID: <strong class="text-cyan-400">{{ $link->circuit_id ?? 'N/A' }}</strong></span>
                @if(!auth()->user()->isReseller() && $link->nttn_link_id)
                    <span>NTTN Link ID: <strong class="text-emerald-400">{{ $link->nttn_link_id }}</strong></span>
                @endif
                <span>Carrier: <strong class="text-slate-200">{{ $link->nttn_provider ?? 'Direct Ring' }}</strong></span>
                <span>Customer: <strong class="text-slate-200">{{ $link->customer->name }}</strong></span>
                <span>Activated: <strong class="text-slate-200">{{ $link->activation_date?->format('M d, Y') ?? 'Pending' }}</strong></span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('links.chat', $link) }}" class="px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-cyan-600/30 flex items-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <span>NOC Real-Time Live Chat</span>
            </a>
            <a href="{{ route('tickets.create', ['link_id' => $link->id]) }}" class="px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-rose-600/30 flex items-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Open Incident (TT)</span>
            </a>
        </div>
    </div>

    <!-- Telemetry Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800">
            <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Live Bandwidth</div>
            <div class="text-2xl font-extrabold text-cyan-400 font-mono">{{ $link->bandwidth_mbps }} Mbps</div>
            <div class="text-[11px] text-slate-500 mt-1">Committed CIR/PIR 1:1</div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800">
            <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Optical Layer</div>
            <div class="text-2xl font-extrabold text-emerald-400 font-mono">-16.8 dBm</div>
            <div class="text-[11px] text-slate-500 mt-1">Rx Power Optimal</div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800">
            <div class="text-xs text-slate-400 uppercase font-semibold mb-1">VLAN / Port</div>
            <div class="text-lg font-bold text-white font-mono">{{ $link->vlan ?? 'VLAN-2040' }}</div>
            <div class="text-[11px] text-slate-500 font-mono mt-1">{{ $link->port ?? 'TenGigE-0/1' }}</div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800">
            <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Monthly Billing</div>
            <div class="text-2xl font-extrabold text-amber-400 font-mono">
                ৳ {{ number_format($link->billing?->monthly_charge ?? 0, 0) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">Invoice: {{ $link->billing?->invoice_no ?? 'Active' }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        
        <!-- Left: Bandwidth Modification Desk (1 Col) -->
        <div class="space-y-6">
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="font-bold text-white text-base">Bandwidth & Service Scale</h3>
                    <span class="text-xs text-cyan-400 font-mono">Capacity / Lifecycle</span>
                </div>

                <form method="POST" action="{{ route('links.service-requests.store', $link) }}" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-300 uppercase mb-1">Request Type *</label>
                        <select name="type" id="service_request_type" required class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white" onchange="handleRequestTypeChange(this.value)">
                            <option value="UPGRADE">Bandwidth Upgrade</option>
                            <option value="DOWNGRADE">Bandwidth Downgrade</option>
                            @if($link->canBeDiscontinued())
                                <option value="DISCONTINUE" class="text-rose-400 font-bold">Link Discontinue</option>
                            @else
                                <option value="DISCONTINUE" disabled class="text-slate-500">
                                    Link Discontinue (Locked: After {{ $link->discontinuationEligibleDate()?->format('M d, Y') }})
                                </option>
                            @endif
                        </select>
                    </div>

                    @if(! $link->canBeDiscontinued())
                        <div class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-[11px] text-amber-300 flex items-start gap-2">
                            <span>🔒</span>
                            <span><strong>3-Month Lock-in Period:</strong> Link discontinuation is eligible 3 months after activation date (from <strong>{{ $link->discontinuationEligibleDate()?->format('M d, Y') }}</strong>).</span>
                        </div>
                    @endif

                    <div id="bandwidth_input_container">
                        <label class="block font-semibold text-slate-300 uppercase mb-1">New Bandwidth (Mbps) *</label>
                        <input type="number" id="requested_bandwidth_mbps" name="requested_bandwidth_mbps" value="{{ $link->bandwidth_mbps + 100 }}" min="1" required
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono font-bold">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 uppercase mb-1">Business Reason</label>
                        <textarea name="reason" rows="2" placeholder="e.g. Traffic peak during quarterly billing or reason for scaling/discontinuing..."
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white"></textarea>
                    </div>

                    <button type="submit" id="sr_submit_btn" class="w-full py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl shadow-lg transition-all">
                        Submit Service Request &rarr;
                    </button>
                </form>

                <script>
                    function handleRequestTypeChange(val) {
                        const bwContainer = document.getElementById('bandwidth_input_container');
                        const bwInput = document.getElementById('requested_bandwidth_mbps');
                        const btn = document.getElementById('sr_submit_btn');

                        if (val === 'DISCONTINUE') {
                            bwContainer.style.display = 'none';
                            bwInput.removeAttribute('required');
                            bwInput.value = '0';
                            btn.className = 'w-full py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl shadow-lg transition-all';
                            btn.innerText = 'Submit Discontinue Request →';
                        } else {
                            bwContainer.style.display = 'block';
                            bwInput.setAttribute('required', 'required');
                            if (bwInput.value === '0') {
                                bwInput.value = '{{ $link->bandwidth_mbps }}';
                            }
                            btn.className = 'w-full py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl shadow-lg transition-all';
                            btn.innerText = 'Submit Bandwidth Request →';
                        }
                    }
                </script>
            </div>

            <!-- Customer Details Card -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-3 text-xs">
                <h3 class="font-bold text-white text-sm uppercase tracking-wider border-b border-slate-800 pb-2">Subscriber Endpoint</h3>
                <div>
                    <div class="text-slate-400">Subscriber</div>
                    <div class="text-white font-bold">{{ $link->customer->name }}</div>
                </div>
                <div>
                    <div class="text-slate-400">Address</div>
                    <div class="text-slate-200">{{ $link->customer->address ?? 'N/A' }} ({{ $link->customer->thana ?? '' }}, {{ $link->customer->district ?? '' }})</div>
                </div>
                <div>
                    <div class="text-slate-400">Phone</div>
                    <div class="text-slate-200 font-mono">{{ $link->customer->phone ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <!-- Right: Incident Tickets & Bandwidth Request History (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Open Tickets on this Link -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-white text-base">Trouble Tickets (TT) on this Circuit</h3>
                        <span class="text-xs text-slate-400 font-mono">({{ $link->tickets->count() }})</span>
                    </div>
                    <a href="{{ route('tickets.create', ['link_id' => $link->id]) }}" class="text-xs font-semibold text-rose-400 hover:text-rose-300">+ Open Ticket</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                            <tr>
                                <th class="px-6 py-3">Ticket #</th>
                                <th class="px-6 py-3">Issue Category</th>
                                <th class="px-6 py-3">Priority</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">View</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-medium">
                            @forelse($link->tickets as $ticket)
                                <tr class="hover:bg-slate-800/40 transition-colors">
                                    <td class="px-6 py-4 font-mono font-bold text-rose-400">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="hover:underline">
                                            {{ $ticket->ticket_no }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-slate-200">
                                        {{ $ticket->type }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-0.5 rounded text-xs font-mono font-bold
                                            @if($ticket->priority === 'CRITICAL') bg-rose-500/20 text-rose-400 border border-rose-500/30
                                            @elseif($ticket->priority === 'HIGH') bg-amber-500/20 text-amber-400 border border-amber-500/30
                                            @else bg-slate-800 text-slate-300 @endif">
                                            {{ $ticket->priority }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs text-slate-300">
                                        {{ $ticket->status }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="text-xs font-semibold text-blue-400 hover:underline">
                                            Detail &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-6 text-center text-slate-500 text-xs">
                                        No active or historical incident tickets logged against this circuit.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bandwidth Modification History -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                    <h3 class="font-bold text-white text-base">Bandwidth Scale History</h3>
                    <span class="text-xs text-slate-400 font-mono">({{ $link->serviceRequests->count() }})</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                            <tr>
                                <th class="px-6 py-3">Request #</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Scale</th>
                                <th class="px-6 py-3">Monthly Bill</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-medium">
                            @forelse($link->serviceRequests as $sr)
                                <tr class="hover:bg-slate-800/40 transition-colors">
                                    <td class="px-6 py-4 font-mono font-bold text-indigo-400">
                                        {{ $sr->request_no }}
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs font-bold">
                                        <span class="px-2 py-0.5 rounded
                                            @if($sr->type === 'UPGRADE') bg-emerald-500/20 text-emerald-400 border border-emerald-500/30
                                            @elseif($sr->type === 'DOWNGRADE') bg-amber-500/20 text-amber-400 border border-amber-500/30
                                            @else bg-rose-500/20 text-rose-400 border border-rose-500/30 @endif">
                                            {{ $sr->type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs">
                                        @if($sr->type === 'DISCONTINUE')
                                            <span class="text-rose-400 font-bold">Discontinue (0M)</span>
                                        @else
                                            {{ $sr->old_bandwidth_mbps }}M &rarr; <strong class="text-cyan-400">{{ $sr->requested_bandwidth_mbps }}M</strong>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs text-amber-400 font-bold">
                                        ৳ {{ number_format($sr->new_monthly_charge ?? 0, 0) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($sr->status === 'PENDING_BILLING')
                                            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                                1. Billing Review
                                            </span>
                                        @elseif($sr->status === 'PENDING_MD')
                                            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                                2. MD Approval
                                            </span>
                                        @elseif($sr->status === 'PENDING_TX')
                                            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                                                3. TX / NTTN Coord
                                            </span>
                                        @elseif($sr->status === 'COMPLETED')
                                            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                ✓ Completed & Billed
                                            </span>
                                        @elseif($sr->status === 'APPROVED')
                                            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                                Approved
                                            </span>
                                        @elseif($sr->status === 'REJECTED')
                                            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                                Rejected
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                                {{ $sr->status }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @if($sr->status === 'PENDING_BILLING' && (auth()->user()->isBilling() || auth()->user()->isSuperAdmin()))
                                            <form method="POST" action="{{ route('service-requests.decide', $sr) }}" class="inline-flex items-center gap-1">
                                                @csrf
                                                <input type="hidden" name="action" value="billing_approve">
                                                <input type="number" step="0.01" min="0" name="new_monthly_charge" required
                                                    value="{{ $sr->new_monthly_charge ?? 0 }}"
                                                    class="w-20 px-2 py-0.5 bg-slate-950 border border-amber-500/40 rounded text-xs font-mono font-bold text-amber-300">
                                                <button type="submit" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-500 text-white rounded text-xs font-bold">
                                                    Approve Bill &rarr;
                                                </button>
                                            </form>
                                        @elseif($sr->status === 'PENDING_MD' && (auth()->user()->isMd() || auth()->user()->isSuperAdmin()))
                                            <form method="POST" action="{{ route('service-requests.decide', $sr) }}" class="inline-block">
                                                @csrf
                                                <input type="hidden" name="action" value="md_approve">
                                                <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-xs font-bold">Approve to TX &rarr;</button>
                                            </form>
                                        @elseif($sr->status === 'PENDING_TX' && (auth()->user()->isTransmission() || auth()->user()->isNoc() || auth()->user()->isSuperAdmin()))
                                            <form method="POST" action="{{ route('service-requests.decide', $sr) }}" class="inline-flex items-center gap-1">
                                                @csrf
                                                <input type="hidden" name="action" value="tx_implement">
                                                <button type="submit" class="px-3 py-1 bg-cyan-600 hover:bg-cyan-500 text-white rounded text-xs font-bold">
                                                    Complete with NTTN &rarr;
                                                </button>
                                            </form>
                                        @elseif($sr->status === 'PENDING' && (auth()->user()->isMd() || auth()->user()->isSuperAdmin()))
                                            <form method="POST" action="{{ route('service-requests.decide', $sr) }}" class="inline-block">
                                                @csrf
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold">Approve</button>
                                            </form>
                                        @elseif($sr->status === 'APPROVED' && (auth()->user()->isNoc() || auth()->user()->isTransmission() || auth()->user()->isSuperAdmin()))
                                            <form method="POST" action="{{ route('service-requests.decide', $sr) }}" class="inline-block">
                                                @csrf
                                                <input type="hidden" name="action" value="implement">
                                                <button type="submit" class="px-3 py-1 {{ $sr->type === 'DISCONTINUE' ? 'bg-rose-600 hover:bg-rose-500' : 'bg-cyan-600 hover:bg-cyan-500' }} text-white rounded-lg text-xs font-bold">
                                                    {{ $sr->type === 'DISCONTINUE' ? 'De-commission Circuit' : 'Implement Circuit' }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-[11px] text-slate-500 font-mono">In Progress</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-6 text-center text-slate-500 text-xs">
                                        No bandwidth modification requests submitted yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
