@extends('layouts.app', ['title' => $reseller->company_name . ' — Reseller Profile', 'header' => $reseller->company_name])

@section('content')
<div class="space-y-8">

    <!-- Profile Header Card -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-950/60 via-indigo-950/40 to-slate-900 border border-blue-500/20 p-8 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white font-extrabold text-3xl shadow-xl shadow-blue-500/30">
                    {{ strtoupper(substr($reseller->company_name, 0, 1)) }}
                </div>
                <div>
                    <div class="text-2xl font-extrabold text-white">{{ $reseller->company_name }}</div>
                    <div class="font-mono text-blue-400 text-sm">{{ $reseller->reseller_code }}</div>
                    <div class="text-sm text-slate-300 mt-1">
                        Contact: {{ $reseller->user->name }} &bull; {{ $reseller->phone ?? 'No phone' }}
                    </div>
                    @if($reseller->address)
                        <div class="text-xs text-slate-400">{{ $reseller->address }}</div>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('export.surveys.csv') }}" 
                   class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-slate-700 text-sm font-semibold text-slate-200 transition-colors">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Surveys CSV
                </a>
                @if(auth()->user()->isReseller())
                    <a href="{{ route('surveys.create') }}"
                       class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold text-sm shadow-lg shadow-blue-500/20 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New Survey Request
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- KPI Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @php
        $kpis = [
            ['label' => 'Customers',       'value' => $stats['total_customers'],   'color' => 'text-white'],
            ['label' => 'Active Circuits',  'value' => $stats['active_links'],      'color' => 'text-emerald-400'],
            ['label' => 'Bandwidth (Mbps)', 'value' => number_format($stats['total_bandwidth']), 'color' => 'text-cyan-400'],
            ['label' => 'Total Surveys',   'value' => $stats['total_surveys'],     'color' => 'text-blue-400'],
            ['label' => 'Pending Orders',  'value' => $stats['pending_surveys'],   'color' => 'text-amber-400'],
            ['label' => 'Open Tickets',    'value' => $stats['open_tickets'],      'color' => 'text-rose-400'],
        ];
        @endphp
        @foreach($kpis as $kpi)
            <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 text-center">
                <div class="text-2xl font-extrabold font-mono {{ $kpi['color'] }}">{{ $kpi['value'] }}</div>
                <div class="text-[11px] text-slate-400 uppercase tracking-wider mt-1">{{ $kpi['label'] }}</div>
            </div>
        @endforeach
    </div>

    <!-- Two Column Layout -->
    <div class="grid lg:grid-cols-2 gap-8">

        <!-- Active Circuits -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                <h3 class="font-bold text-white">Active Circuits</h3>
                <a href="{{ route('export.links.csv') }}" class="text-xs text-emerald-400 hover:text-emerald-300 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export CSV
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                        <tr>
                            <th class="px-5 py-3">Link ID</th>
                            <th class="px-5 py-3">Customer</th>
                            <th class="px-5 py-3">Capacity</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($activeLinks as $link)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-5 py-3">
                                    <a href="{{ route('links.show', $link) }}" class="font-mono text-cyan-400 hover:underline font-bold text-xs">{{ $link->link_id }}</a>
                                </td>
                                <td class="px-5 py-3 text-slate-200 text-xs">{{ $link->customer->name }}</td>
                                <td class="px-5 py-3 font-mono text-white font-bold text-xs">{{ $link->bandwidth_mbps }} Mbps</td>
                                <td class="px-5 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $link->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-6 text-center text-slate-500 text-sm">No active circuits.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Surveys -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                <h3 class="font-bold text-white">Recent Survey Requests</h3>
                <a href="{{ route('surveys.index') }}" class="text-xs text-blue-400 hover:text-blue-300">View All →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                        <tr>
                            <th class="px-5 py-3">Request #</th>
                            <th class="px-5 py-3">Customer</th>
                            <th class="px-5 py-3">Mbps</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($recentSurveys as $survey)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-5 py-3">
                                    <a href="{{ route('surveys.show', $survey) }}" class="font-mono text-blue-400 hover:underline font-bold text-xs">{{ $survey->request_no }}</a>
                                </td>
                                <td class="px-5 py-3 text-slate-200 text-xs">{{ $survey->customer->name }}</td>
                                <td class="px-5 py-3 font-mono text-cyan-400 font-bold text-xs">{{ $survey->bandwidth_mbps }}</td>
                                <td class="px-5 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold
                                        @if($survey->status === 'ACTIVE') bg-emerald-500/10 text-emerald-400
                                        @elseif(str_contains($survey->status, 'REJECTED')) bg-rose-500/10 text-rose-400
                                        @elseif(str_contains($survey->status, 'MD_APPROVAL')) bg-amber-500/10 text-amber-400
                                        @else bg-blue-500/10 text-blue-400 @endif border border-current/20">
                                        {{ $survey->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-6 text-center text-slate-500 text-sm">No surveys yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customer List -->
        <div class="lg:col-span-2 bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                <h3 class="font-bold text-white">Customer Portfolio ({{ $reseller->customers->count() }})</h3>
                <a href="{{ route('customers.index') }}" class="text-xs text-blue-400 hover:text-blue-300">View All →</a>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 p-5">
                @forelse($reseller->customers as $customer)
                    <a href="{{ route('customers.show', $customer) }}"
                       class="block p-4 rounded-xl bg-slate-950/60 border border-slate-800 hover:border-slate-600 transition-colors group">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center justify-center font-bold text-sm">
                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-200 group-hover:text-white">{{ $customer->name }}</div>
                                <div class="text-xs text-slate-400">{{ $customer->thana }}, {{ $customer->district }}</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">{{ $customer->links->count() }} circuit(s)</span>
                            <span class="text-blue-400 group-hover:text-blue-300">Profile →</span>
                        </div>
                    </a>
                @empty
                    <div class="col-span-3 text-center py-8 text-slate-500 text-sm">No customers registered.</div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection
