@extends('layouts.app', ['title' => 'Trouble Tickets', 'header' => 'Incident Management & Trouble Ticketing (TT)'])

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('tickets.index') }}" class="flex items-center gap-3 flex-1 max-w-xl">
            <select name="status" onchange="this.form.submit()" class="px-3 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-slate-300 focus:outline-none focus:border-blue-500">
                <option value="">All Statuses</option>
                <option value="OPEN" {{ request('status') === 'OPEN' ? 'selected' : '' }}>Open</option>
                <option value="WORKING" {{ request('status') === 'WORKING' ? 'selected' : '' }}>Working</option>
                <option value="RESOLVED" {{ request('status') === 'RESOLVED' ? 'selected' : '' }}>Resolved</option>
                <option value="CLOSED" {{ request('status') === 'CLOSED' ? 'selected' : '' }}>Closed</option>
            </select>

            <select name="priority" onchange="this.form.submit()" class="px-3 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-slate-300 focus:outline-none focus:border-blue-500">
                <option value="">All Priorities</option>
                <option value="CRITICAL" {{ request('priority') === 'CRITICAL' ? 'selected' : '' }}>Critical</option>
                <option value="HIGH" {{ request('priority') === 'HIGH' ? 'selected' : '' }}>High</option>
                <option value="NORMAL" {{ request('priority') === 'NORMAL' ? 'selected' : '' }}>Normal</option>
                <option value="LOW" {{ request('priority') === 'LOW' ? 'selected' : '' }}>Low</option>
            </select>
        </form>

        <a href="{{ route('tickets.create') }}" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-sm shadow-lg shadow-rose-600/25 flex items-center gap-2 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Open Trouble Ticket</span>
        </a>
    </div>

    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Ticket #</th>
                        <th class="px-6 py-4">Circuit Ref</th>
                        <th class="px-6 py-4">Subscriber</th>
                        <th class="px-6 py-4">Issue Summary</th>
                        <th class="px-6 py-4">Priority</th>
                        <th class="px-6 py-4">Assigned To</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($tickets as $t)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-rose-400">
                                <a href="{{ route('tickets.show', $t) }}" class="hover:underline">
                                    {{ $t->ticket_no }}
                                </a>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $t->created_at->timezone(config('app.timezone', 'Asia/Dhaka'))->format('M d, Y h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-cyan-400">
                                <a href="{{ route('links.show', $t->link) }}" class="hover:underline">
                                    {{ $t->link->link_id }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-white font-semibold">
                                {{ $t->link->customer->name }}
                            </td>
                            <td class="px-6 py-4 text-slate-200">
                                {{ $t->type }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold
                                    @if($t->priority === 'CRITICAL') bg-rose-500/20 text-rose-400 border border-rose-500/30
                                    @elseif($t->priority === 'HIGH') bg-amber-500/20 text-amber-400 border border-amber-500/30
                                    @else bg-slate-800 text-slate-300 @endif">
                                    {{ $t->priority }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-300">
                                {{ $t->assignedEngineer->name ?? 'Unassigned' }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold
                                    @if($t->is_customer_verified) bg-emerald-500/10 text-emerald-400 border border-emerald-500/30
                                    @elseif($t->status === 'RESOLVED') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                    @elseif($t->status === 'CLOSED') bg-slate-700/50 text-slate-400 border border-slate-600/30
                                    @elseif($t->status === 'WORKING') bg-cyan-500/10 text-cyan-400 border border-cyan-500/20
                                    @else bg-rose-500/10 text-rose-400 border border-rose-500/20 @endif">
                                    {{ $t->is_customer_verified ? 'CLOSED ✓' : $t->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($t->isLocked())
                                        <a href="{{ route('tickets.show', $t) }}" class="px-3 py-1.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-xs font-semibold text-slate-400 hover:text-white transition-colors inline-flex items-center gap-1.5">
                                            <span>🔒</span>
                                            <span>View History &rarr;</span>
                                        </a>
                                    @else
                                        <a href="{{ route('tickets.show', $t) }}" class="px-3.5 py-1.5 rounded-xl bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 text-xs font-semibold transition-colors inline-block">
                                            Handle &rarr;
                                        </a>
                                    @endif
                                    @if(auth()->user()->isSuperAdmin())
                                        <form method="POST" action="{{ route('tickets.destroy', $t) }}" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete trouble ticket {{ $t->ticket_no }}? All message logs and attachments will be deleted.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Delete Ticket" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-slate-500">
                                No incident trouble tickets logged.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
