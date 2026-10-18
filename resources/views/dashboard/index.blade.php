@extends('layouts.app', ['title' => 'Dashboard', 'header' => 'Operational Command Center'])

@section('content')
<div class="space-y-8">

    <!-- Hero / Welcome Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-950/60 via-indigo-950/40 to-slate-900 border border-blue-500/20 p-8 shadow-2xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-mono mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    NTTN Core Mesh: Optimal 99.99% Optical SLA
                </div>
                <h2 class="text-3xl font-extrabold text-white tracking-tight">
                    Hello, {{ auth()->user()->name }}
                </h2>
                <p class="text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                    You are logged in with <strong class="text-white uppercase font-mono">{{ $role }}</strong> authority. 
                    Manage transmission requests, optical feasibility, NOC testing, and commissioning workflows.
                </p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                @if(auth()->user()->isReseller())
                    <a href="{{ route('surveys.create') }}" class="px-5 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold text-sm shadow-lg shadow-blue-500/30 flex items-center gap-2 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>New Survey Request</span>
                    </a>
                @endif
                @if(auth()->user()->isMd() || auth()->user()->isSuperAdmin())
                    <a href="{{ route('approvals.index') }}" class="px-5 py-3 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-semibold text-sm shadow-lg shadow-amber-600/30 flex items-center gap-2 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Review Approvals ({{ $metrics['pending_approvals'] + $metrics['final_approvals'] }})</span>
                    </a>
                @endif
                @if(auth()->user()->isNoc() || auth()->user()->isSuperAdmin())
                    <a href="{{ route('noc.index') }}" class="px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm shadow-lg shadow-emerald-600/30 flex items-center gap-2 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                        <span>NOC Testing Workbench</span>
                    </a>
                @endif
                @if(auth()->user()->isTransmission() || auth()->user()->isSuperAdmin())
                    <a href="{{ route('work-orders.index') }}" class="px-5 py-3 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-semibold text-sm shadow-lg shadow-cyan-600/30 flex items-center gap-2 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span>Field Work Orders</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
        <!-- Surveys Card -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all shadow-lg group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Surveys</span>
                <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-white font-mono">{{ $metrics['total_surveys'] }}</div>
            <div class="text-xs text-slate-400 mt-1">Lifecycle pipeline</div>
        </div>

        <!-- Active Links Card -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all shadow-lg group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Live Active Circuits</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-emerald-400 font-mono">{{ $metrics['active_links'] }}</div>
            <div class="text-xs text-slate-400 mt-1">Passing live telemetry</div>
        </div>

        <!-- MD Approvals Pending -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all shadow-lg group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">MD Approvals Queue</span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-amber-400 font-mono">{{ $metrics['pending_approvals'] + $metrics['final_approvals'] }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $metrics['pending_approvals'] }} Capex + {{ $metrics['final_approvals'] }} Final</div>
        </div>

        <!-- Open Trouble Tickets -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all shadow-lg group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Open Tickets (TT)</span>
                <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-400 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-rose-400 font-mono">{{ $metrics['open_tickets'] }}</div>
            <div class="text-xs text-slate-400 mt-1">Under NOC investigation</div>
        </div>
    </div>

    <!-- Main Tables Grid -->
    <div class="grid lg:grid-cols-3 gap-8">
        
        <!-- Left: Surveys & Orders (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-white text-base">In-Flight Survey & Work Order Queue</h3>
                        <span class="text-xs text-slate-400 font-mono">({{ $recentSurveys->count() }})</span>
                    </div>
                    <a href="{{ route('surveys.index') }}" class="text-xs font-semibold text-blue-400 hover:text-blue-300">View All &rarr;</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                            <tr>
                                <th class="px-6 py-3">Request No</th>
                                <th class="px-6 py-3">Customer & Location</th>
                                <th class="px-6 py-3">Bandwidth</th>
                                <th class="px-6 py-3">Stage / Status</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-medium">
                            @forelse($recentSurveys as $survey)
                                <tr class="hover:bg-slate-800/40 transition-colors">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('surveys.show', $survey) }}" class="font-mono text-blue-400 hover:underline font-bold">
                                            {{ $survey->request_no }}
                                        </a>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ $survey->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-slate-100 font-semibold">{{ $survey->customer->name }}</div>
                                        <div class="text-xs text-slate-400">{{ $survey->customer->thana ?? '' }}, {{ $survey->customer->district ?? '' }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-cyan-400 font-bold">
                                        {{ $survey->bandwidth_mbps }} Mbps
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-mono font-bold
                                            @if($survey->status === 'ACTIVE') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                            @elseif(str_contains($survey->status, 'MD_APPROVAL')) bg-amber-500/10 text-amber-400 border border-amber-500/20
                                            @elseif(str_contains($survey->status, 'REJECTED')) bg-rose-500/10 text-rose-400 border border-rose-500/20
                                            @elseif(str_contains($survey->status, 'WORK')) bg-cyan-500/10 text-cyan-400 border border-cyan-500/20
                                            @elseif(str_contains($survey->status, 'NOC')) bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                            @elseif(str_contains($survey->status, 'BILLING')) bg-violet-500/10 text-violet-400 border border-violet-500/20
                                            @else bg-blue-500/10 text-blue-400 border border-blue-500/20 @endif">
                                            {{ $survey->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('surveys.show', $survey) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 transition-colors">
                                            Open &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                        No survey requests found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Active Circuits Table -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-white text-base">Active Telecom Circuits</h3>
                        <span class="text-xs text-slate-400 font-mono">({{ $recentLinks->count() }})</span>
                    </div>
                    <a href="{{ route('links.index') }}" class="text-xs font-semibold text-blue-400 hover:text-blue-300">All Circuits &rarr;</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                            <tr>
                                <th class="px-6 py-3">Link / Circuit ID</th>
                                <th class="px-6 py-3">Customer</th>
                                <th class="px-6 py-3">Capacity</th>
                                <th class="px-6 py-3">NTTN Provider</th>
                                <th class="px-6 py-3 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-medium">
                            @forelse($recentLinks as $link)
                                <tr class="hover:bg-slate-800/40 transition-colors">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('links.show', $link) }}" class="font-mono text-cyan-400 hover:underline font-bold">
                                            {{ $link->link_id }}
                                        </a>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ $link->circuit_id ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-200">
                                        {{ $link->customer->name }}
                                    </td>
                                    <td class="px-6 py-4 font-mono font-bold text-white">
                                        {{ $link->bandwidth_mbps }} Mbps
                                    </td>
                                    <td class="px-6 py-4 text-slate-400">
                                        {{ $link->nttn_provider ?? 'Direct Ring' }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            {{ $link->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-6 text-center text-slate-500">
                                        No active links in production yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right: Activity Audit Feed & Trouble Tickets (1 Col) -->
        <div class="space-y-6">
            
            <!-- Trouble Tickets Card -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                    <h3 class="font-bold text-white text-base">Active Incident Tickets</h3>
                    <a href="{{ route('tickets.index') }}" class="text-xs font-semibold text-rose-400 hover:text-rose-300">View All &rarr;</a>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($recentTickets as $ticket)
                        <a href="{{ route('tickets.show', $ticket) }}" class="block p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80 hover:border-slate-700 transition-colors">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-mono text-xs font-bold text-rose-400">{{ $ticket->ticket_no }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded font-mono font-bold
                                    @if($ticket->priority === 'CRITICAL') bg-rose-500/20 text-rose-400 border border-rose-500/30
                                    @elseif($ticket->priority === 'HIGH') bg-amber-500/20 text-amber-400 border border-amber-500/30
                                    @else bg-slate-800 text-slate-300 @endif">
                                    {{ $ticket->priority }}
                                </span>
                            </div>
                            <div class="text-xs font-semibold text-slate-200 line-clamp-1">{{ $ticket->type }}</div>
                            <div class="text-[11px] text-slate-400 mt-1 flex items-center justify-between">
                                <span>{{ $ticket->link->link_id }}</span>
                                <span class="font-mono">{{ $ticket->status }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="text-xs text-slate-500 text-center py-4">No open tickets. All optical circuits operating nominally.</div>
                    @endforelse
                </div>
            </div>

            <!-- Activity Logs Feed -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                    <h3 class="font-bold text-white text-base">Audit Trail Feed</h3>
                    <a href="{{ route('activity-logs.index') }}" class="text-xs font-semibold text-slate-400 hover:text-slate-200">Full Trail &rarr;</a>
                </div>
                <div class="p-4 space-y-3 font-mono text-xs">
                    @forelse($recentActivities as $act)
                        <div class="p-3 rounded-xl bg-slate-950/50 border border-slate-800/60">
                            <div class="flex items-center justify-between text-slate-400 mb-1">
                                <span class="text-blue-400 font-bold">{{ $act->action }}</span>
                                <span class="text-[10px]">{{ $act->created_at?->diffForHumans() }}</span>
                            </div>
                            <div class="text-slate-300 text-[11px] line-clamp-2 font-sans">{{ $act->remarks ?? 'Status transition recorded' }}</div>
                            <div class="text-[10px] text-slate-500 mt-1">Actor: {{ $act->user?->name ?? 'System' }}</div>
                        </div>
                    @empty
                        <div class="text-slate-500 text-center py-4">No activity logged yet.</div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
