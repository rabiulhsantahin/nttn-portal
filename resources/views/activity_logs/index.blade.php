@extends('layouts.app', ['title' => 'Audit Trail', 'header' => 'System-Wide Activity & Compliance Audit Trail'])

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Immutable Operational Activity Feed</h2>
            <p class="text-xs text-slate-400 mt-1">Every state transition, approval, field update, and technical verification is cryptographically logged.</p>
        </div>

        <form method="GET" action="{{ route('activity-logs.index') }}" class="flex items-center gap-3">
            <input type="text" name="action" value="{{ request('action') }}" placeholder="Filter by action..."
                class="px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500">
            
            <select name="entity_type" onchange="this.form.submit()" class="px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:border-blue-500">
                <option value="">All Entities</option>
                <option value="survey_request" {{ request('entity_type') === 'survey_request' ? 'selected' : '' }}>Survey Requests</option>
                <option value="work_order" {{ request('entity_type') === 'work_order' ? 'selected' : '' }}>Work Orders</option>
                <option value="noc_handover" {{ request('entity_type') === 'noc_handover' ? 'selected' : '' }}>NOC Handovers</option>
                <option value="link" {{ request('entity_type') === 'link' ? 'selected' : '' }}>Active Circuits</option>
                <option value="ticket" {{ request('entity_type') === 'ticket' ? 'selected' : '' }}>Trouble Tickets</option>
                <option value="service_request" {{ request('entity_type') === 'service_request' ? 'selected' : '' }}>Bandwidth Scale</option>
            </select>
        </form>
    </div>

    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800 font-mono">
                    <tr>
                        <th class="px-6 py-4">Timestamp</th>
                        <th class="px-6 py-4">Entity</th>
                        <th class="px-6 py-4">Action</th>
                        <th class="px-6 py-4">State Transition</th>
                        <th class="px-6 py-4">Remarks</th>
                        <th class="px-6 py-4 text-right">Actor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-slate-400">
                                {{ $log->created_at?->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-cyan-400">
                                {{ $log->entity_type }} #{{ $log->entity_id }}
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-xs text-blue-400">
                                {{ $log->action }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                @if($log->from_status || $log->to_status)
                                    <span class="text-slate-400">{{ $log->from_status ?? 'START' }}</span>
                                    <span class="text-slate-600">&rarr;</span>
                                    <span class="text-emerald-400 font-bold">{{ $log->to_status }}</span>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-200 max-w-md">
                                {{ $log->remarks ?? 'Logged action' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="text-xs font-bold text-white">{{ $log->user?->name ?? 'System' }}</div>
                                <div class="text-[10px] text-slate-400 font-mono uppercase">{{ $log->user?->role?->name ?? 'SYSTEM' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                                No activity audit entries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-800">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
