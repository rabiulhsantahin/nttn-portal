@extends('layouts.app', ['title' => 'Billing & Invoicing', 'header' => 'Commercial Billing & Invoicing Engine'])

@section('content')
<div class="space-y-8">

    <!-- KPI Summary: Active Circuits Consolidated Billing Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Monthly Bill for All Active Circuits -->
        <div class="p-6 rounded-3xl bg-gradient-to-br from-emerald-950/60 via-slate-900 to-slate-900 border border-emerald-500/30 shadow-xl space-y-2 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Total Active Monthly Bill</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-sm">৳</span>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-white font-mono tracking-tight">
                ৳ {{ number_format($totalMonthlyBill, 2) }}
            </div>
            <p class="text-[11px] text-emerald-300/80 font-medium">
                @if($isReseller)
                    Combined monthly bill for {{ $totalActiveCircuits }} active {{ Str::plural('circuit', $totalActiveCircuits) }}
                @else
                    @if($selectedResellerId)
                        Filtered monthly bill for {{ $resellers->firstWhere('id', $selectedResellerId)?->company_name }}
                    @else
                        All resellers combined monthly bill ({{ $totalActiveCircuits }} active {{ Str::plural('circuit', $totalActiveCircuits) }})
                    @endif
                @endif
            </p>
        </div>

        <!-- Card 2: Total Active Circuits -->
        <div class="p-6 rounded-3xl bg-gradient-to-br from-cyan-950/60 via-slate-900 to-slate-900 border border-cyan-500/30 shadow-xl space-y-2 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-cyan-500/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-cyan-400">Active Circuits</span>
                <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-white font-mono tracking-tight">
                {{ $totalActiveCircuits }}
            </div>
            <p class="text-[11px] text-slate-400 font-medium">
                Live operational transmission circuits
            </p>
        </div>

        <!-- Card 3: Total Active Bandwidth -->
        <div class="p-6 rounded-3xl bg-gradient-to-br from-blue-950/60 via-slate-900 to-slate-900 border border-blue-500/30 shadow-xl space-y-2 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-400">Total Bandwidth</span>
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-white font-mono tracking-tight">
                {{ number_format($totalActiveBandwidth) }} <span class="text-base text-blue-400 font-sans">Mbps</span>
            </div>
            <p class="text-[11px] text-slate-400 font-medium">
                Total provisioned optical capacity
            </p>
        </div>

        <!-- Card 4: Total Installation Capex -->
        <div class="p-6 rounded-3xl bg-gradient-to-br from-amber-950/60 via-slate-900 to-slate-900 border border-amber-500/30 shadow-xl space-y-2 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Total Capex Fee</span>
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-white font-mono tracking-tight">
                ৳ {{ number_format($totalInstallationBill, 2) }}
            </div>
            <p class="text-[11px] text-slate-400 font-medium">
                One-time installation charges total
            </p>
        </div>
    </div>

    <!-- Multi-Tenant Reseller Filter Bar (For MD & Billing staff) -->
    @if(! $isReseller)
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-violet-500/10 text-violet-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-white">Central Resellers Billing Desk (সকল রিসেলার বিলিং ডেস্ক)</h4>
                    <p class="text-xs text-slate-400">MD ও Billing পোর্টালে সকল রিসেলারের সক্রিয় সার্কিট ও বিলিং দেখা যাচ্ছে। নির্দিষ্ট রিসেলার ফিল্টার করতে পারবেন।</p>
                </div>
            </div>

            <form method="GET" action="{{ route('billing.index') }}" class="flex items-center gap-2">
                <label for="reseller_filter" class="text-xs font-semibold text-slate-400 whitespace-nowrap">রিসেলার ফিল্টার:</label>
                <select id="reseller_filter" name="reseller_id" onchange="this.form.submit()"
                    class="px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs font-medium text-white focus:outline-none focus:border-violet-500 shadow-inner">
                    <option value="">All Resellers (সকল রিসেলার)</option>
                    @foreach($resellers as $r)
                        <option value="{{ $r->id }}" {{ $selectedResellerId == $r->id ? 'selected' : '' }}>
                            {{ $r->company_name }} ({{ $r->reseller_code }})
                        </option>
                    @endforeach
                </select>
                @if($selectedResellerId)
                    <a href="{{ route('billing.index') }}" class="px-3 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-medium transition-colors">
                        Reset
                    </a>
                @endif
            </form>
        </div>
    @endif

    <!-- Section 1: Active Circuits Consolidated Billing Breakdown -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="px-6 py-4 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-950/40">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
                <h3 class="font-bold text-white text-base">Active Circuits Consolidated Billing Breakdown</h3>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono text-emerald-400 font-bold bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                    Total Active Bill: ৳ {{ number_format($totalMonthlyBill, 2) }} / mo
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Circuit / Link ID</th>
                        <th class="px-6 py-4">Customer</th>
                        @if(! $isReseller)
                            <th class="px-6 py-4">Reseller</th>
                        @endif
                        <th class="px-6 py-4">NTTN Carrier</th>
                        <th class="px-6 py-4">Bandwidth</th>
                        <th class="px-6 py-4">Installation (Capex)</th>
                        <th class="px-6 py-4">Monthly Bill (Opex)</th>
                        <th class="px-6 py-4">Activation Date</th>
                        <th class="px-6 py-4 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($activeLinks as $l)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 font-mono">
                                <a href="{{ route('links.show', $l) }}" class="font-bold text-cyan-400 hover:text-cyan-300 hover:underline block">
                                    {{ $l->circuit_id ?? $l->link_id }}
                                </a>
                                <span class="text-xs text-slate-400">{{ $l->link_id }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-white font-bold">{{ $l->customer->name }}</div>
                                <div class="text-xs text-slate-400">{{ $l->customer->district ?? 'N/A' }}</div>
                            </td>
                            @if(! $isReseller)
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-violet-500/10 text-violet-300 border border-violet-500/20">
                                        <svg class="w-3.5 h-3.5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        {{ $l->reseller->company_name ?? 'N/A' }}
                                    </span>
                                </td>
                            @endif
                            <td class="px-6 py-4 text-xs font-semibold text-slate-300">
                                <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700">
                                    {{ $l->nttn_provider ?? 'Summit Communications' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono text-blue-400 font-bold">
                                {{ $l->bandwidth_mbps }} Mbps
                            </td>
                            <td class="px-6 py-4 font-mono text-amber-400">
                                ৳ {{ number_format($l->billing?->installation_charge ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-emerald-400 font-extrabold text-base">
                                ৳ {{ number_format($l->billing?->monthly_charge ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-300">
                                {{ $l->activation_date ? $l->activation_date->format('M d, Y') : ($l->created_at->format('M d, Y')) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    ACTIVE
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ! $isReseller ? '9' : '8' }}" class="px-6 py-8 text-center text-slate-500">
                                No active circuits currently provisioned.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($activeLinks->isNotEmpty())
                    <tfoot class="bg-slate-950/80 border-t-2 border-emerald-500/30 text-xs">
                        <tr>
                            <td colspan="{{ ! $isReseller ? '4' : '3' }}" class="px-6 py-4 font-bold text-white uppercase tracking-wider text-sm">
                                Total Active Circuits Combined (সর্বমোট বিল):
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-blue-400 text-sm">
                                {{ number_format($totalActiveBandwidth) }} Mbps
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-amber-400 text-sm">
                                ৳ {{ number_format($totalInstallationBill, 2) }}
                            </td>
                            <td class="px-6 py-4 font-mono font-extrabold text-emerald-400 text-lg">
                                ৳ {{ number_format($totalMonthlyBill, 2) }} <span class="text-xs font-normal text-emerald-300">/mo</span>
                            </td>
                            <td colspan="2" class="px-6 py-4 text-right font-mono text-xs text-slate-400">
                                {{ $totalActiveCircuits }} Active {{ Str::plural('Circuit', $totalActiveCircuits) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Section 2: Pending Activations Requiring Invoicing (Staff Only) -->
    @if(! $isReseller && $pendingSurveys->isNotEmpty())
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
                        @foreach($pendingSurveys as $s)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 font-mono font-bold text-blue-400">
                                    <div>{{ $s->request_no }}</div>
                                    @if($s->workOrder?->link?->billing?->status === 'RETURNED')
                                        <span class="inline-block px-2 py-0.5 mt-1 rounded text-[10px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                             Returned by MD
                                        </span>
                                    @endif
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
                                    <a href="{{ route('surveys.show', $s) }}" class="px-4 py-2 {{ $s->workOrder?->link?->billing?->status === 'RETURNED' ? 'bg-amber-600 hover:bg-amber-500' : 'bg-violet-600 hover:bg-violet-500' }} text-white rounded-xl text-xs font-bold shadow-md transition-all inline-block">
                                        {{ $s->workOrder?->link?->billing?->status === 'RETURNED' ? 'Correct Invoice →' : 'Issue Invoice →' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Section 3: Commercial Invoices Ledger -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
            <h3 class="font-bold text-white text-base">Commercial Invoices Ledger</h3>
            <span class="text-xs text-slate-400 font-mono">Completed Billing Records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Invoice #</th>
                        <th class="px-6 py-4">Circuit / Customer</th>
                        @if(! $isReseller)
                            <th class="px-6 py-4">Reseller</th>
                        @endif
                        <th class="px-6 py-4">Installation Fee</th>
                        <th class="px-6 py-4">Monthly Rate</th>
                        @if(! $isReseller)
                            <th class="px-6 py-4">Carrier Cost</th>
                        @endif
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
                                <div class="text-xs text-slate-400 font-mono">{{ $b->link->link_id }} ({{ $b->link->circuit_id }})</div>
                            </td>
                            @if(! $isReseller)
                                <td class="px-6 py-4">
                                    <span class="text-xs font-semibold text-slate-300">
                                        {{ $b->link->reseller->company_name ?? 'N/A' }}
                                    </span>
                                </td>
                            @endif
                            <td class="px-6 py-4 font-mono text-amber-400 font-bold">
                                ৳ {{ number_format($b->installation_charge, 2) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-emerald-400 font-bold">
                                ৳ {{ number_format($b->monthly_charge, 2) }}
                            </td>
                            @if(! $isReseller)
                                <td class="px-6 py-4 font-mono text-slate-400">
                                    ৳ {{ number_format($b->nttn_cost, 2) }}
                                </td>
                            @endif
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
                            <td colspan="{{ ! $isReseller ? '8' : '6' }}" class="px-6 py-8 text-center text-slate-500">
                                No billing invoice records logged yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($billings->hasPages())
            <div class="px-6 py-4 border-t border-slate-800">
                {{ $billings->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

