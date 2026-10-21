@extends('layouts.app', ['title' => 'Bandwidth Requests', 'header' => 'Bandwidth Scaling: Upgrade & Downgrade Queue'])

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Bandwidth Modification Workflow</h2>
            <p class="text-xs text-slate-400 mt-1">Multi-stage approval pipeline: Commercial Billing review &rarr; MD executive approval &rarr; Transmission NTTN coordination & bill finalization.</p>
        </div>
    </div>

    <!-- Workflow Pipeline Step Indicator -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
            <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 font-bold flex items-center justify-center font-mono">1</div>
                <div>
                    <div class="font-bold text-white">Billing Department</div>
                    <div class="text-[11px] text-slate-400">Review & set new monthly charge</div>
                </div>
            </div>
            <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 font-bold flex items-center justify-center font-mono">2</div>
                <div>
                    <div class="font-bold text-white">MD Approval</div>
                    <div class="text-[11px] text-slate-400">Executive authorization to proceed</div>
                </div>
            </div>
            <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-cyan-500/20 text-cyan-400 font-bold flex items-center justify-center font-mono">3</div>
                <div>
                    <div class="font-bold text-white">Transmission (TX)</div>
                    <div class="text-[11px] text-slate-400">Coordinate with NTTN & configure</div>
                </div>
            </div>
            <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 font-bold flex items-center justify-center font-mono">4</div>
                <div>
                    <div class="font-bold text-white">Direct Finalization</div>
                    <div class="text-[11px] text-slate-400">Circuit & live billing automatically updated</div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-4">Request #</th>
                        <th class="px-5 py-4">Circuit / Customer</th>
                        <th class="px-5 py-4">Type</th>
                        <th class="px-5 py-4">Scale</th>
                        <th class="px-5 py-4">Monthly Bill (৳)</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Remarks</th>
                        <th class="px-5 py-4 text-right">Workflow Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($requests as $sr)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-4 font-mono font-bold text-indigo-400">
                                {{ $sr->request_no }}
                                <div class="text-[11px] text-slate-500 font-mono">{{ $sr->created_at->format('M d, H:i') }}</div>
                                <div class="text-[10px] text-slate-400 font-sans mt-0.5">By: {{ $sr->creator->name ?? 'Requester' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <a href="{{ route('links.show', $sr->link) }}" class="font-mono text-cyan-400 font-bold hover:underline">
                                    {{ $sr->link->link_id }}
                                </a>
                                <div class="text-white text-xs font-semibold">{{ $sr->link->customer->name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $sr->link->circuit_id }} ({{ $sr->link->nttn_provider }})</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="px-2 py-0.5 rounded text-xs font-mono font-bold
                                    @if($sr->type === 'UPGRADE') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                    @elseif($sr->type === 'DOWNGRADE') bg-amber-500/10 text-amber-400 border border-amber-500/20
                                    @else bg-rose-500/10 text-rose-400 border border-rose-500/20 @endif">
                                    {{ $sr->type }}
                                </span>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs">
                                @if($sr->type === 'DISCONTINUE')
                                    <span class="text-rose-400 font-bold">Discontinue (0M)</span>
                                @else
                                    <span class="text-slate-400">{{ $sr->old_bandwidth_mbps }}M</span> &rarr; <strong class="text-cyan-400">{{ $sr->requested_bandwidth_mbps }}M</strong>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-mono text-xs">
                                <div class="text-slate-400">Current: ৳ {{ number_format($sr->link->billing?->monthly_charge ?? 0, 0) }}</div>
                                <div class="text-amber-400 font-bold">
                                    Target: ৳ {{ number_format($sr->new_monthly_charge ?? 0, 0) }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @if($sr->status === 'PENDING_BILLING')
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">
                                        1. Billing Review
                                    </span>
                                @elseif($sr->status === 'PENDING_MD')
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/30">
                                        2. MD Approval
                                    </span>
                                @elseif($sr->status === 'PENDING_TX')
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/30">
                                        3. TX / NTTN Coord
                                    </span>
                                @elseif($sr->status === 'COMPLETED')
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                        ✓ Completed & Billed
                                    </span>
                                @elseif($sr->status === 'APPROVED')
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-blue-500/10 text-blue-400 border border-blue-500/30">
                                        Approved (Ready)
                                    </span>
                                @elseif($sr->status === 'REJECTED')
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-rose-500/10 text-rose-400 border border-rose-500/30">
                                        Rejected
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-slate-500/10 text-slate-400 border border-slate-500/30">
                                        {{ $sr->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-300 max-w-xs">
                                <div>{{ $sr->reason ?? 'No requester remarks' }}</div>
                                @if($sr->tx_remarks)
                                    <div class="text-[11px] text-cyan-400 font-mono mt-1">NTTN: {{ $sr->tx_remarks }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">

                                    {{-- Stage 1: Billing Review --}}
                                    @if($sr->status === 'PENDING_BILLING' && (auth()->user()->isBilling() || auth()->user()->isSuperAdmin()))
                                        <form method="POST" action="{{ route('service-requests.decide', $sr) }}" class="flex items-center gap-1.5">
                                            @csrf
                                            <input type="hidden" name="action" value="billing_approve">
                                            <div class="relative">
                                                <span class="absolute left-2 top-1.5 text-slate-500 font-mono text-xs">৳</span>
                                                <input type="number" step="0.01" min="0" name="new_monthly_charge" required
                                                    value="{{ $sr->new_monthly_charge ?? 0 }}"
                                                    title="Set New Monthly Charge"
                                                    class="w-24 pl-5 pr-2 py-1 bg-slate-950 border border-amber-500/40 rounded-lg text-xs font-mono font-bold text-amber-300">
                                            </div>
                                            <button type="submit" class="px-3 py-1 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-xs font-bold shadow whitespace-nowrap">
                                                Approve Commercials &rarr;
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('service-requests.decide', $sr) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" onclick="return confirm('Reject this bandwidth request?')" class="px-2.5 py-1 bg-rose-600/30 hover:bg-rose-600 text-rose-300 hover:text-white rounded-lg text-xs font-bold border border-rose-500/40">
                                                Reject
                                            </button>
                                        </form>

                                    {{-- Stage 2: MD Approval --}}
                                    @elseif($sr->status === 'PENDING_MD' && (auth()->user()->isMd() || auth()->user()->isSuperAdmin()))
                                        <form method="POST" action="{{ route('service-requests.decide', $sr) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="md_approve">
                                            <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold shadow flex items-center gap-1">
                                                Approve to TX Team &rarr;
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('service-requests.decide', $sr) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" onclick="return confirm('Reject this request?')" class="px-2.5 py-1.5 bg-rose-600/30 hover:bg-rose-600 text-rose-300 hover:text-white rounded-lg text-xs font-bold border border-rose-500/40">
                                                Reject
                                            </button>
                                        </form>

                                    {{-- Stage 3: Transmission (TX) NTTN Coordination & Final Implementation --}}
                                    @elseif($sr->status === 'PENDING_TX' && (auth()->user()->isTransmission() || auth()->user()->isNoc() || auth()->user()->isSuperAdmin()))
                                        <form method="POST" action="{{ route('service-requests.decide', $sr) }}" class="flex items-center gap-1.5">
                                            @csrf
                                            <input type="hidden" name="action" value="tx_implement">
                                            <input type="text" name="tx_remarks" placeholder="NTTN update remarks..."
                                                class="w-36 px-2 py-1 bg-slate-950 border border-cyan-500/40 rounded-lg text-xs text-white">
                                            <button type="submit" class="px-3 py-1 bg-cyan-600 hover:bg-cyan-500 text-white rounded-lg text-xs font-bold shadow whitespace-nowrap">
                                                Update NTTN & Finalize Bill &rarr;
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('service-requests.decide', $sr) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" onclick="return confirm('Reject this request?')" class="px-2 py-1 bg-rose-600/30 hover:bg-rose-600 text-rose-300 hover:text-white rounded-lg text-xs font-bold border border-rose-500/40">
                                                Reject
                                            </button>
                                        </form>

                                    {{-- Legacy / Discontinue PENDING & APPROVED --}}
                                    @elseif($sr->status === 'PENDING' && (auth()->user()->isMd() || auth()->user()->isSuperAdmin()))
                                        <form method="POST" action="{{ route('service-requests.decide', $sr) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow">
                                                Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('service-requests.decide', $sr) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="px-3 py-1 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-bold shadow">
                                                Reject
                                            </button>
                                        </form>
                                    @elseif($sr->status === 'APPROVED' && (auth()->user()->isNoc() || auth()->user()->isTransmission() || auth()->user()->isSuperAdmin()))
                                        <form method="POST" action="{{ route('service-requests.decide', $sr) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="implement">
                                            <button type="submit" class="px-3.5 py-1.5 {{ $sr->type === 'DISCONTINUE' ? 'bg-rose-600 hover:bg-rose-500' : 'bg-cyan-600 hover:bg-cyan-500' }} text-white rounded-lg text-xs font-bold shadow">
                                                {{ $sr->type === 'DISCONTINUE' ? 'De-commission Circuit →' : 'Implement Circuit →' }}
                                            </button>
                                        </form>
                                    @else
                                        @if($sr->status === 'PENDING_BILLING')
                                            <span class="text-xs text-amber-400 font-mono">Awaiting Billing</span>
                                        @elseif($sr->status === 'PENDING_MD')
                                            <span class="text-xs text-indigo-400 font-mono">Awaiting MD</span>
                                        @elseif($sr->status === 'PENDING_TX')
                                            <span class="text-xs text-cyan-400 font-mono">Awaiting TX/NTTN</span>
                                        @elseif($sr->status === 'COMPLETED')
                                            <span class="text-xs text-emerald-400 font-mono">✓ Finalized</span>
                                        @else
                                            <span class="text-xs text-slate-500 font-mono">{{ $sr->status }}</span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-slate-500">
                                No bandwidth change requests in queue.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
