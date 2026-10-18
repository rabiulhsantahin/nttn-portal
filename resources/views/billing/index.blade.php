@extends('layouts.app', ['title' => 'Billing & Invoicing', 'header' => 'Commercial Billing & Invoicing Engine'])

@section('content')
<div class="space-y-8">

    <!-- Section 1: Pending Activations Requiring Billing -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-violet-400"></span>
                <h3 class="font-bold text-white text-base">Verified Activations Awaiting Commercial Invoicing</h3>
            </div>
            <span class="text-xs font-mono text-violet-400 font-bold bg-violet-500/10 px-2.5 py-0.5 rounded-full border border-violet-500/20">
                {{ $pendingSurveys->count() }} Orders
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Request #</th>
                        <th class="px-6 py-4">Customer & Reseller</th>
                        <th class="px-6 py-4">Bandwidth</th>
                        <th class="px-6 py-4">Capex Estimate</th>
                        <th class="px-6 py-4">Monthly Estimate</th>
                        <th class="px-6 py-4 text-right">Invoicing Action</th>
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
                                ৳ {{ number_format($s->feedback?->installation_cost ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-300">
                                ৳ {{ number_format($s->feedback?->monthly_cost ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('surveys.show', $s) }}" class="px-4 py-2 bg-violet-600 hover:bg-violet-500 text-white rounded-xl text-xs font-bold shadow-md transition-all inline-block">
                                    Issue Invoice &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                No links currently waiting for initial billing.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Invoiced Records Ledger -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
            <h3 class="font-bold text-white text-base">Commercial Billing Ledger</h3>
            <span class="text-xs text-slate-400 font-mono">Completed Invoices</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Invoice #</th>
                        <th class="px-6 py-4">Circuit / Customer</th>
                        <th class="px-6 py-4">Installation Fee</th>
                        <th class="px-6 py-4">Monthly Rate</th>
                        <th class="px-6 py-4">Carrier Cost</th>
                        <th class="px-6 py-4">Billing Cycle</th>
                        <th class="px-6 py-4 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($billings as $b)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-violet-400">
                                {{ $b->invoice_no }}
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('links.show', $b->link) }}" class="text-white font-bold hover:underline">
                                    {{ $b->link->customer->name }}
                                </a>
                                <div class="text-xs text-slate-400 font-mono">{{ $b->link->link_id }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-amber-400 font-bold">
                                ৳ {{ number_format($b->installation_charge, 2) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-white font-bold">
                                ৳ {{ number_format($b->monthly_charge, 2) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-400">
                                ৳ {{ number_format($b->nttn_cost, 2) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-300">
                                {{ $b->billing_start_date?->format('M d, Y') }} &rarr; {{ $b->due_date?->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    {{ $b->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                                No billing records logged yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
