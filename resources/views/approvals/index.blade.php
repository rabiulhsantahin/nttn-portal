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
                                {{ $s->feedback?->fiber_distance_km ?? 'N/A' }} km
                            </td>
                            <td class="px-6 py-4 font-mono text-amber-400 font-bold">
                                ৳ {{ number_format($s->feedback?->installation_cost ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-emerald-400 font-bold">
                                ৳ {{ number_format($s->feedback?->monthly_cost ?? 0, 2) }}
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
                                {{ $s->workOrder?->link?->circuit_id ?? 'Pending' }}
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

</div>
@endsection
