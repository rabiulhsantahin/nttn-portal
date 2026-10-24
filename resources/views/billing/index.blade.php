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

            <div class="flex flex-wrap items-center gap-3">
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

                <a href="{{ route('billing.legacy.create') }}" class="px-4 py-2.5 bg-gradient-to-r from-violet-600 via-indigo-600 to-blue-600 hover:from-violet-500 hover:to-blue-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-violet-600/30 flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Manual Legacy Entry (আগের ডাটা এন্ট্রি)</span>
                </a>
            </div>
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
                        <th class="px-6 py-4">Unit Price</th>
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
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <a href="{{ route('links.show', $l) }}" class="font-bold text-cyan-400 hover:text-cyan-300 hover:underline">
                                        {{ $l->circuit_id ?? $l->link_id }}
                                    </a>
                                    @if($l->is_legacy)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                            LEGACY
                                        </span>
                                    @endif
                                </div>
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
                            <td class="px-6 py-4 font-mono text-cyan-400 font-bold">
                                <div>৳ {{ number_format($l->unit_price, 2) }}</div>
                                <span class="text-[10px] text-slate-400 font-normal">/ Mbps</span>
                            </td>
                            <td class="px-6 py-4 font-mono text-amber-400">
                                ৳ {{ number_format($l->billing?->installation_charge ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-emerald-400 font-extrabold text-base">
                                <div>৳ {{ number_format($l->billing?->monthly_charge ?? 0, 2) }}</div>
                                @if(! $isReseller && $l->pendingRateRevision)
                                    <div class="mt-1">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 whitespace-nowrap">
                                            ⏳ Rev. Pending: ৳ {{ number_format($l->pendingRateRevision->new_monthly_charge, 2) }}
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-300">
                                {{ $l->activation_date ? $l->activation_date->format('M d, Y') : ($l->created_at->format('M d, Y')) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    ACTIVE
                                </span>
                                @if(! $isReseller)
                                    <div class="mt-2">
                                        <button type="button" onclick="openRateModal({{ $l->id }}, '{{ $l->circuit_id ?? $l->link_id }}', '{{ addslashes($l->customer->name) }}', {{ (float) ($l->billing?->monthly_charge ?? 0) }}, {{ (float) ($l->billing?->installation_charge ?? 0) }}, {{ (float) ($l->billing?->nttn_cost ?? 0) }}, {{ (int) $l->bandwidth_mbps }}, {{ (int) $l->reseller_id }}, '{{ addslashes($l->nttn_provider ?? '') }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[11px] font-bold transition-all shadow-sm">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            <span>Edit Rate</span>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ! $isReseller ? '10' : '9' }}" class="px-6 py-8 text-center text-slate-500">
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
                            <td class="px-6 py-4 font-mono font-bold text-cyan-400 text-sm">
                                <div>৳ {{ number_format($totalActiveBandwidth > 0 ? ($totalMonthlyBill / $totalActiveBandwidth) : 0, 2) }}</div>
                                <span class="text-[10px] text-slate-400 font-normal">avg / Mbps</span>
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
                                <div class="flex items-center justify-end gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        {{ $b->status }}
                                    </span>
                                    @if(auth()->user()->isSuperAdmin())
                                        <form method="POST" action="{{ route('billing.destroy', $b) }}" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete billing invoice {{ $b->invoice_no }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Delete Invoice Record" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
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

    <!-- Section 4: Manual Legacy Circuits & Approval Ledger -->
    @if($legacyLinks->isNotEmpty())
        <div class="bg-slate-900/80 border border-amber-500/30 rounded-3xl overflow-hidden shadow-2xl">
            <div class="px-6 py-4 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-950/40">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                    <h3 class="font-bold text-white text-base">Manual Legacy Circuits & Billing History (পূর্ববর্তী সার্কিট ও অনুমোদন স্থিতি)</h3>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono text-amber-400 font-bold bg-amber-500/10 px-3 py-1 rounded-full border border-amber-500/20">
                        {{ $legacyLinks->count() }} Legacy Records
                    </span>
                    @if(! $isReseller)
                        <a href="{{ route('billing.legacy.create') }}" class="px-3 py-1 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold transition-all">
                            + Add Another
                        </a>
                    @endif
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
                            <th class="px-6 py-4">Carrier & Bandwidth</th>
                            <th class="px-6 py-4">Monthly Rate</th>
                            <th class="px-6 py-4">MD Approval Reference / Doc</th>
                            <th class="px-6 py-4">Activation Date</th>
                            <th class="px-6 py-4 text-right">Approval Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        @foreach($legacyLinks as $leg)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 font-mono">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        @if($leg->status === 'ACTIVE')
                                            <a href="{{ route('links.show', $leg) }}" class="font-bold text-cyan-400 hover:underline">
                                                {{ $leg->circuit_id ?? $leg->link_id }}
                                            </a>
                                        @else
                                            <span class="font-bold text-slate-300">
                                                {{ $leg->circuit_id ?? $leg->link_id }}
                                            </span>
                                        @endif
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                            LEGACY
                                        </span>
                                    </div>
                                    <span class="text-xs text-slate-400">{{ $leg->link_id }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-white font-bold">{{ $leg->customer->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $leg->customer->district ?? 'N/A' }}</div>
                                </td>
                                @if(! $isReseller)
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-violet-500/10 text-violet-300 border border-violet-500/20">
                                            {{ $leg->reseller->company_name ?? 'N/A' }}
                                        </span>
                                    </td>
                                @endif
                                <td class="px-6 py-4">
                                    <div class="font-mono text-blue-400 font-bold">{{ $leg->bandwidth_mbps }} Mbps</div>
                                    <div class="text-xs text-slate-400">{{ $leg->nttn_provider }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono text-emerald-400 font-bold text-base">
                                    ৳ {{ number_format($leg->billing?->monthly_charge ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 text-xs font-mono">
                                    <div class="text-amber-300 font-semibold">
                                        {{ $leg->md_approval_ref ?? 'Legacy Memo' }}
                                    </div>
                                    @if($leg->md_approval_doc)
                                        <a href="{{ asset('storage/' . $leg->md_approval_doc) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-cyan-400 hover:text-cyan-300 hover:underline mt-0.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span>View Approval Doc</span>
                                        </a>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-300">
                                    {{ $leg->activation_date ? $leg->activation_date->format('M d, Y') : 'Pending' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($leg->status === 'ACTIVE')
                                        <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            ✓ Approved & Live
                                        </span>
                                    @elseif($leg->status === 'PENDING')
                                        <div class="inline-flex flex-col items-end gap-1">
                                            <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                                ⏳ Awaiting MD Approval
                                            </span>
                                            @if(auth()->user()->isMd() || auth()->user()->isSuperAdmin())
                                                <a href="{{ route('approvals.index') }}" class="text-[11px] text-amber-300 hover:underline font-semibold">
                                                    Review in MD Desk &rarr;
                                                </a>
                                            @endif
                                        </div>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                            {{ $leg->status }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Section 5: Rate Revision History Ledger -->
    @if($rateRevisions->isNotEmpty())
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
            <div class="px-6 py-4 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-950/40">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-cyan-400"></span>
                    <h3 class="font-bold text-white text-base">Circuit Rate Revisions History (রেট পরিবর্তনের ইতিহাস ও অডিট ট্রেইল)</h3>
                </div>
                <span class="text-xs font-mono text-cyan-400 font-bold bg-cyan-500/10 px-2.5 py-0.5 rounded-full border border-cyan-500/20">
                    {{ $rateRevisions->count() }} Records
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-4">Circuit / Customer</th>
                            @if(! $isReseller)
                                <th class="px-6 py-4">Reseller</th>
                            @endif
                            <th class="px-6 py-4">Old Rate &rarr; New Rate</th>
                            <th class="px-6 py-4">Revision Reason</th>
                            <th class="px-6 py-4">Requested / Approved</th>
                            <th class="px-6 py-4 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        @foreach($rateRevisions as $rr)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 font-mono">
                                    <a href="{{ route('links.show', $rr->link) }}" class="font-bold text-cyan-400 hover:underline">
                                        {{ $rr->link->circuit_id ?? $rr->link->link_id }}
                                    </a>
                                    <div class="text-xs text-slate-400 font-sans">{{ $rr->link->customer->name }}</div>
                                </td>
                                @if(! $isReseller)
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-semibold text-slate-300">
                                            {{ $rr->link->reseller->company_name ?? 'N/A' }}
                                        </span>
                                    </td>
                                @endif
                                <td class="px-6 py-4 font-mono text-sm">
                                    <span class="text-slate-400">৳ {{ number_format($rr->old_monthly_charge, 2) }}</span>
                                    <span class="text-slate-500 mx-1.5">&rarr;</span>
                                    <span class="text-emerald-400 font-extrabold">৳ {{ number_format($rr->new_monthly_charge, 2) }}</span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-300">
                                    <div>{{ $rr->reason }}</div>
                                    @if($rr->remarks)
                                        <div class="text-[11px] text-slate-400 italic mt-0.5">Note: {{ $rr->remarks }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-slate-300">
                                    <div>Req: {{ $rr->requester->name ?? 'Billing' }}</div>
                                    @if($rr->approver)
                                        <div class="text-emerald-400 text-[11px]">Approved: {{ $rr->approver->name }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold
                                        @if($rr->status === 'APPROVED') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                        @elseif($rr->status === 'PENDING') bg-amber-500/10 text-amber-400 border border-amber-500/20
                                        @else bg-rose-500/10 text-rose-400 border border-rose-500/20 @endif">
                                        {{ $rr->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

<!-- Quick Rate Edit Modal (For Billing & MD staff) -->
@if(! $isReseller)
    <div id="rate_edit_modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
        <div class="bg-slate-900 border border-slate-700 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl relative space-y-5">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-sm">৳</span>
                    <div>
                        <h4 class="text-base font-bold text-white">Circuit Rate Revision (রেট পরিবর্তন)</h4>
                        <p class="text-xs text-slate-400" id="modal_circuit_subtitle">Circuit rate adjustment desk</p>
                    </div>
                </div>
                <button type="button" onclick="closeRateModal()" class="text-slate-400 hover:text-white text-lg font-bold p-1">&times;</button>
            </div>

            <form id="rate_modal_form" method="POST" action="" class="space-y-4 text-xs">
                @csrf
                <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 flex justify-between items-center text-xs">
                    <div>
                        <span class="text-slate-400 uppercase font-semibold">Current Active Bill:</span>
                        <div class="text-base font-bold font-mono text-slate-200" id="modal_current_bill">৳ 0.00</div>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400 uppercase font-semibold">Active Unit Price:</span>
                        <div class="text-sm font-bold font-mono text-cyan-400" id="modal_current_unit_price">৳ 0.00 / Mbps</div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="modal_monthly_charge" class="block font-bold uppercase text-emerald-400">
                        New Monthly Bill (নতুন মাসিক বিল ৳) <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-bold text-emerald-400">৳</span>
                        <input type="number" step="0.01" min="0" name="monthly_charge" id="modal_monthly_charge" required
                            class="w-full pl-7 pr-3 py-2 bg-slate-950 border border-emerald-500/40 rounded-xl text-white font-mono font-bold text-base focus:outline-none focus:border-emerald-400">
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-slate-400">
                        <span>Takes effect in the reseller portal upon MD Sir's approval.</span>
                        <span id="modal_proposed_unit_price" class="font-mono text-cyan-400 font-bold"></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label for="modal_installation_charge" class="block font-semibold uppercase text-slate-300">
                            Installation Fee (৳)
                        </label>
                        <input type="number" step="0.01" min="0" name="installation_charge" id="modal_installation_charge"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono focus:outline-none focus:border-amber-500">
                    </div>
                    <div class="space-y-1">
                        <label for="modal_nttn_cost" class="block font-semibold uppercase text-slate-300">
                            Carrier NTTN Cost (৳)
                        </label>
                        <input type="number" step="0.01" min="0" name="nttn_cost" id="modal_nttn_cost"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono focus:outline-none focus:border-slate-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label for="modal_reseller_id" class="block font-semibold uppercase text-slate-300">
                            Reseller (রেসেলার)
                        </label>
                        <select name="reseller_id" id="modal_reseller_id"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-medium focus:outline-none focus:border-violet-500">
                            @foreach($resellers as $reseller)
                                <option value="{{ $reseller->id }}">{{ $reseller->company_name }} ({{ $reseller->reseller_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="modal_nttn_provider" class="block font-semibold uppercase text-slate-300">
                            NTTN Carrier (এনটিটিএন কোম্পানি)
                        </label>
                        <select name="nttn_provider" id="modal_nttn_provider"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-medium focus:outline-none focus:border-cyan-500">
                            <option value="Summit Communications">Summit Communications</option>
                            <option value="Fiber@Home">Fiber@Home</option>
                            <option value="Bahon Limited">Bahon Limited</option>
                            <option value="Direct Ring Core">Direct Ring Core</option>
                            <option value="Other NTTN">Other NTTN</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-1">
                    <label for="modal_effective_date" class="block font-semibold uppercase text-slate-300">
                        Effective Billing Date
                    </label>
                    <input type="date" name="effective_date" id="modal_effective_date" value="{{ date('Y-m-d') }}"
                        class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono focus:outline-none focus:border-cyan-500">
                </div>

                <div class="space-y-1">
                    <label for="modal_reason" class="block font-semibold uppercase text-slate-300">
                        Reason / Memo Reference <span class="text-rose-400">*</span>
                    </label>
                    <textarea name="reason" id="modal_reason" rows="2.5" required
                        placeholder="State reason (e.g. As per MD memo #54, rate adjusted to ৳ 40,000 for client discount)..."
                        class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-violet-500"></textarea>
                </div>

                @if(auth()->user()->isMd() || auth()->user()->isSuperAdmin())
                    <label class="flex items-center gap-2 p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 cursor-pointer">
                        <input type="checkbox" name="direct_approve" value="1" class="rounded text-amber-500 focus:ring-0">
                        <span class="text-[11px] font-bold text-amber-300">Directly Approve & Apply Now (তাৎক্ষণিক কার্যকর করুন)</span>
                    </label>
                @endif

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="closeRateModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-semibold">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-cyan-600 hover:from-emerald-500 hover:to-cyan-500 text-white rounded-xl font-bold shadow-lg shadow-emerald-600/30">
                        Submit for MD Approval &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let modalActiveBandwidth = 0;

        function updateProposedUnitPrice() {
            const val = parseFloat(document.getElementById('modal_monthly_charge').value) || 0;
            const el = document.getElementById('modal_proposed_unit_price');
            if (modalActiveBandwidth > 0 && el) {
                const up = (val / modalActiveBandwidth).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                el.textContent = `New Unit Price: ৳ ${up} / Mbps`;
            }
        }

        document.getElementById('modal_monthly_charge')?.addEventListener('input', updateProposedUnitPrice);

        function openRateModal(linkId, circuitId, customerName, currentMonthly, currentInstallation, currentNttnCost, bandwidth, resellerId, nttnProvider) {
            modalActiveBandwidth = bandwidth || 0;
            const form = document.getElementById('rate_modal_form');
            form.action = `/billing/links/${linkId}/revise-rate`;

            document.getElementById('modal_circuit_subtitle').textContent = `Circuit: ${circuitId} (${customerName}) • ${modalActiveBandwidth} Mbps`;
            document.getElementById('modal_current_bill').textContent = `৳ ${Number(currentMonthly).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
            const currentUp = modalActiveBandwidth > 0 ? (currentMonthly / modalActiveBandwidth).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
            document.getElementById('modal_current_unit_price').textContent = `৳ ${currentUp} / Mbps`;

            document.getElementById('modal_monthly_charge').value = currentMonthly;
            document.getElementById('modal_installation_charge').value = currentInstallation;
            document.getElementById('modal_nttn_cost').value = currentNttnCost;
            if (document.getElementById('modal_reseller_id')) {
                document.getElementById('modal_reseller_id').value = resellerId || '';
            }
            if (document.getElementById('modal_nttn_provider')) {
                const nttnEl = document.getElementById('modal_nttn_provider');
                if (nttnProvider) {
                    let exists = false;
                    for (let i = 0; i < nttnEl.options.length; i++) {
                        if (nttnEl.options[i].value === nttnProvider) {
                            exists = true;
                            break;
                        }
                    }
                    if (!exists) {
                        const opt = document.createElement('option');
                        opt.value = nttnProvider;
                        opt.textContent = nttnProvider;
                        nttnEl.appendChild(opt);
                    }
                    nttnEl.value = nttnProvider;
                }
            }
            document.getElementById('modal_reason').value = '';
            updateProposedUnitPrice();

            document.getElementById('rate_edit_modal').classList.remove('hidden');
        }

        function closeRateModal() {
            document.getElementById('rate_edit_modal').classList.add('hidden');
        }
    </script>
@endif
@endsection

