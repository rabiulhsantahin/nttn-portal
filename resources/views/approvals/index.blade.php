@extends('layouts.app', ['title' => 'Executive Approvals', 'header' => 'Managing Director Commercial Approval Center'])

@section('content')
<div class="space-y-8">

    <!-- Header Description -->
    <div class="bg-gradient-to-r from-amber-950/40 via-slate-900 to-slate-900 border border-amber-500/20 rounded-3xl p-6 shadow-xl">
        <h2 class="text-xl font-bold text-white mb-1">Executive Decision Queue</h2>
        <p class="text-xs text-slate-300">
            Review optical feasibility feasibility studies, Capex/Opex expenditure, and give final commissioning authorization for live circuits.
        </p>
    </div>

    <!-- Section 1: Pre-Construction Feasibility Approvals -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                <h3 class="font-bold text-white text-base">Stage 1: Pre-Construction Feasibility Approvals (Capex / Opex Review)</h3>
            </div>
            <span class="text-xs font-mono text-amber-400 font-bold bg-amber-500/10 px-2.5 py-0.5 rounded-full border border-amber-500/20">
                {{ $pendingSurveys->total() }} Pending
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Request #</th>
                        <th class="px-6 py-4">Reseller & Customer</th>
                        <th class="px-6 py-4">Bandwidth</th>
                        <th class="px-6 py-4">Fiber Dist</th>
                        <th class="px-6 py-4">Installation Capex</th>
                        <th class="px-6 py-4">Monthly Cost</th>
                        <th class="px-6 py-4 text-right">Review Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($pendingSurveys as $s)
                        @php
                            $activeFb = ($s->selectedFeedback && $s->selectedFeedback->feasible)
                                ? $s->selectedFeedback
                                : ($s->feedbacks->firstWhere('feasible', true) ?? $s->feedback);
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-blue-400">
                                {{ $s->request_no }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-white font-bold">{{ $s->customer->name }}</div>
                                <div class="text-xs text-slate-400">{{ $s->reseller->company_name }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-cyan-400 font-bold">
                                {{ $s->bandwidth_mbps }} Mbps
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-300">
                                {{ $activeFb?->fiber_distance_km ?? 'N/A' }} km
                            </td>
                            <td class="px-6 py-4 font-mono text-amber-400 font-bold">
                                ৳ {{ number_format($activeFb?->installation_cost ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-emerald-400 font-bold">
                                ৳ {{ number_format($activeFb?->monthly_cost ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('surveys.show', $s) }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-bold shadow-md transition-all inline-block">
                                    Open Decision Desk &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                                No surveys currently awaiting MD Capex approval.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Final Commissioning Sign-Offs -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                <h3 class="font-bold text-white text-base">Stage 2: Final Commissioning & Go-Live Authorizations</h3>
            </div>
            <span class="text-xs font-mono text-emerald-400 font-bold bg-emerald-500/10 px-2.5 py-0.5 rounded-full border border-emerald-500/20">
                {{ $pendingCommissioning->total() }} Ready
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Request #</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Provisioned Circuit</th>
                        <th class="px-6 py-4">Invoice #</th>
                        <th class="px-6 py-4">SLA Testing Status</th>
                        <th class="px-6 py-4 text-right">Commissioning Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($pendingCommissioning as $s)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-blue-400">
                                {{ $s->request_no }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-white font-bold">{{ $s->customer->name }}</div>
                                <div class="text-xs text-slate-400">{{ $s->reseller->company_name }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-cyan-400 font-bold">
                                <div>{{ $s->workOrder?->link?->circuit_id ?? 'Pending' }}</div>
                                @if(!auth()->user()->isReseller() && $s->workOrder?->link?->nttn_link_id)
                                    <div class="text-[11px] text-emerald-400 font-normal">NTTN: {{ $s->workOrder->link->nttn_link_id }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-violet-400">
                                {{ $s->workOrder?->link?->billing?->invoice_no ?? 'Cleared' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    8-Point SLA PASSED
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('surveys.show', $s) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-md transition-all inline-block">
                                    Grant Final Approval &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                No circuits awaiting final MD commissioning sign-off.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 3: Manual Legacy Circuits Awaiting MD Approval -->
    <div class="bg-slate-900/80 border border-amber-500/30 rounded-3xl overflow-hidden shadow-2xl">
        <div class="px-6 py-4 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-950/40">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                <h3 class="font-bold text-white text-base">Stage 3: Manual Legacy Circuits & Historical Billing Approval (পূর্ববর্তী সার্কিট অনুমোদন)</h3>
            </div>
            <span class="text-xs font-mono text-amber-400 font-bold bg-amber-500/10 px-2.5 py-0.5 rounded-full border border-amber-500/20">
                {{ $pendingLegacy->total() }} Pending MD Sign-Off
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Circuit / Link ID</th>
                        <th class="px-6 py-4">Reseller & Customer</th>
                        <th class="px-6 py-4">Bandwidth & NTTN</th>
                        <th class="px-6 py-4">Monthly Rate</th>
                        <th class="px-6 py-4">MD Memo Ref / Doc</th>
                        <th class="px-6 py-4">Activation Date</th>
                        <th class="px-6 py-4 text-right">Executive Decision</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($pendingLegacy as $leg)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-cyan-400">
                                <div>{{ $leg->circuit_id ?? $leg->link_id }}</div>
                                <span class="text-xs text-slate-400 font-normal">{{ $leg->link_id }}</span>
                                <span class="inline-block px-1.5 py-0.2 mt-1 rounded text-[9px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                    LEGACY ENTRY
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-white font-bold">{{ $leg->customer->name }}</div>
                                <div class="text-xs text-violet-300 font-semibold">{{ $leg->reseller->company_name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $leg->customer->district ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-mono text-blue-400 font-bold">{{ $leg->bandwidth_mbps }} Mbps</div>
                                <div class="text-xs text-slate-400">{{ $leg->nttn_provider }}</div>
                                @if($leg->vlan || $leg->port)
                                    <div class="text-[10px] text-slate-500 font-mono">VLAN: {{ $leg->vlan ?? 'N/A' }} | Port: {{ $leg->port ?? 'N/A' }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono">
                                <div class="text-emerald-400 font-bold text-base">৳ {{ number_format($leg->billing?->monthly_charge ?? 0, 2) }}</div>
                                <div class="text-[11px] text-amber-400 font-medium">Capex: ৳ {{ number_format($leg->billing?->installation_charge ?? 0, 2) }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <div class="text-amber-300 font-mono font-semibold">{{ $leg->md_approval_ref ?? 'Legacy Memo' }}</div>
                                @if($leg->legacy_notes)
                                    <div class="text-[11px] text-slate-300 max-w-xs truncate" title="{{ $leg->legacy_notes }}">
                                        {{ $leg->legacy_notes }}
                                    </div>
                                @endif
                                @if($leg->md_approval_doc)
                                    <a href="{{ asset('storage/' . $leg->md_approval_doc) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-cyan-400 hover:text-cyan-300 hover:underline mt-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>View Scanned Document</span>
                                    </a>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-300">
                                {{ $leg->activation_date ? $leg->activation_date->format('M d, Y') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('approvals.legacy.decide', $leg) }}" onsubmit="return confirm('Confirm approval for Legacy Circuit {{ $leg->circuit_id ?? $leg->link_id }}? This will activate the circuit and publish it in {{ $leg->reseller->company_name }}\'s portal.');">
                                        @csrf
                                        <input type="hidden" name="decision" value="approve">
                                        <input type="hidden" name="remarks" value="Approved by MD. Legacy circuit live in reseller portal.">
                                        <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Approve & Activate</span>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('approvals.legacy.decide', $leg) }}" onsubmit="return confirm('Are you sure you want to reject this legacy entry?');">
                                        @csrf
                                        <input type="hidden" name="decision" value="reject">
                                        <input type="hidden" name="remarks" value="Rejected by MD.">
                                        <button type="submit" class="px-3 py-2 bg-rose-600/20 hover:bg-rose-600/40 text-rose-300 border border-rose-500/30 rounded-xl text-xs font-semibold transition-all">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                                No manual legacy circuits currently awaiting MD approval.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pendingLegacy->hasPages())
            <div class="px-6 py-4 border-t border-slate-800">
                {{ $pendingLegacy->links() }}
            </div>
        @endif
    </div>

    <!-- Section 4: Circuit Commercial Rate Revision Approvals -->
    <div class="bg-slate-900/80 border border-emerald-500/30 rounded-3xl overflow-hidden shadow-2xl">
        <div class="px-6 py-4 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-950/40">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                <h3 class="font-bold text-white text-base">Stage 4: Circuit Commercial Rate Revision Approvals (সার্কিট রেট সংশোধন অনুমোদন)</h3>
            </div>
            <span class="text-xs font-mono text-emerald-400 font-bold bg-emerald-500/10 px-2.5 py-0.5 rounded-full border border-emerald-500/20">
                {{ $pendingRateRevisions->total() }} Pending MD Review
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Circuit / Link ID</th>
                        <th class="px-6 py-4">Reseller & Customer</th>
                        <th class="px-6 py-4">Current Rate &rarr; Proposed Rate</th>
                        <th class="px-6 py-4">Revision Reason</th>
                        <th class="px-6 py-4">Requested By</th>
                        <th class="px-6 py-4 text-right">Executive Decision</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($pendingRateRevisions as $rev)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 font-mono">
                                <a href="{{ route('links.show', $rev->link) }}" class="font-bold text-cyan-400 hover:underline">
                                    {{ $rev->link->circuit_id ?? $rev->link->link_id }}
                                </a>
                                <div class="text-xs text-slate-400">{{ $rev->link->link_id }}</div>
                                <div class="text-[10px] text-blue-400 font-bold mt-0.5">{{ $rev->link->bandwidth_mbps }} Mbps ({{ $rev->link->nttn_provider }})</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-white font-bold">{{ $rev->link->customer->name }}</div>
                                <div class="text-xs text-violet-300 font-semibold">{{ $rev->link->reseller->company_name }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-slate-400 text-xs line-through">৳ {{ number_format($rev->old_monthly_charge, 2) }}</span>
                                    <span class="text-slate-500">&rarr;</span>
                                    <span class="text-emerald-400 font-extrabold text-base">৳ {{ number_format($rev->new_monthly_charge, 2) }}</span>
                                </div>
                                @if($rev->link->bandwidth_mbps > 0)
                                    <div class="text-[10px] text-cyan-400 font-bold mt-0.5">
                                        Unit: ৳ {{ number_format($rev->old_monthly_charge / $rev->link->bandwidth_mbps, 2) }} &rarr; ৳ {{ number_format($rev->new_monthly_charge / $rev->link->bandwidth_mbps, 2) }} / Mbps
                                    </div>
                                @endif
                                <div class="text-[11px] text-slate-400 mt-0.5">Effective: {{ $rev->effective_date ? $rev->effective_date->format('M d, Y') : 'Immediate' }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-300">
                                <div class="max-w-xs">{{ $rev->reason }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-slate-300">
                                <div>{{ $rev->requester->name ?? 'Billing Officer' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $rev->created_at->format('M d, Y H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('approvals.rate-revisions.decide', $rev) }}" onsubmit="return confirm('Confirm rate revision for Circuit {{ $rev->link->circuit_id ?? $rev->link->link_id }}? The new monthly rate of ৳ {{ number_format($rev->new_monthly_charge, 2) }} will immediately be reflected in {{ $rev->link->reseller->company_name }}\'s portal.');">
                                        @csrf
                                        <input type="hidden" name="decision" value="approve">
                                        <input type="hidden" name="remarks" value="Approved by MD. Rate updated.">
                                        <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Approve & Update Rate</span>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('approvals.rate-revisions.decide', $rev) }}" onsubmit="return confirm('Reject this rate revision? Current rate will remain unchanged.');">
                                        @csrf
                                        <input type="hidden" name="decision" value="reject">
                                        <input type="hidden" name="remarks" value="Rejected by MD.">
                                        <button type="submit" class="px-3 py-2 bg-rose-600/20 hover:bg-rose-600/40 text-rose-300 border border-rose-500/30 rounded-xl text-xs font-semibold transition-all">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                No circuit rate revision requests currently awaiting MD approval.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pendingRateRevisions->hasPages())
            <div class="px-6 py-4 border-t border-slate-800">
                {{ $pendingRateRevisions->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
