@extends('layouts.app', ['title' => 'Ticket ' . $ticket->ticket_no, 'header' => 'Incident Workbench: ' . $ticket->ticket_no])

@section('content')
<div class="space-y-8">

    <!-- Top Summary Card -->
    <div class="bg-gradient-to-r from-rose-950/40 via-slate-900 to-slate-900 border border-rose-500/30 rounded-3xl p-6 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-extrabold text-white font-mono">{{ $ticket->ticket_no }}</h2>
                <span class="px-3 py-1 rounded-full text-xs font-mono font-bold
                    @if($ticket->priority === 'CRITICAL') bg-rose-500/20 text-rose-400 border border-rose-500/30
                    @elseif($ticket->priority === 'HIGH') bg-amber-500/20 text-amber-400 border border-amber-500/30
                    @else bg-slate-800 text-slate-300 @endif">
                    {{ $ticket->priority }} PRIORITY
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-slate-800 text-slate-300">
                    STATUS: {{ $ticket->status }}
                </span>
            </div>
            <div class="text-xs text-slate-400 mt-2 font-mono">
                Affected Circuit: <a href="{{ route('links.show', $ticket->link) }}" class="text-cyan-400 font-bold hover:underline">{{ $ticket->link->link_id }}</a> 
                | Customer: <strong class="text-slate-200">{{ $ticket->link->customer->name }}</strong>
                | Logged by: <strong class="text-slate-200">{{ $ticket->creator->name }}</strong> ({{ $ticket->created_at->format('M d, Y H:i') }})
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('links.chat', $ticket->link) }}" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <span>NOC Chat</span>
            </a>
            <a href="{{ route('tickets.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold">
                &larr; Back
            </a>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        
        <!-- Left: Incident Details (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <h3 class="font-bold text-white text-base border-b border-slate-800 pb-3">Incident Description</h3>
                <div class="text-sm font-semibold text-rose-400 font-mono">{{ $ticket->type }}</div>
                <div class="p-4 bg-slate-950/60 rounded-2xl border border-slate-800 text-sm text-slate-200 leading-relaxed whitespace-pre-wrap">
                    {{ $ticket->description }}
                </div>

                @if($ticket->resolved_at)
                    <div class="text-xs text-emerald-400 font-mono flex items-center gap-2 pt-2">
                        <span>&check; Resolved at: {{ $ticket->resolved_at->format('M d, Y H:i') }}</span>
                    </div>
                @endif
                @if($ticket->closed_at)
                    <div class="text-xs text-slate-400 font-mono flex items-center gap-2">
                        <span>&check; Ticket Closed at: {{ $ticket->closed_at->format('M d, Y H:i') }}</span>
                    </div>
                @endif
            </div>

            <!-- Activity Logs on this ticket -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                    <h3 class="font-bold text-white text-base">Resolution Audit History</h3>
                </div>
                <div class="p-6 space-y-3 font-mono text-xs">
                    @forelse($activities as $act)
                        <div class="p-3 bg-slate-950/50 rounded-xl border border-slate-800/80">
                            <div class="flex items-center justify-between text-slate-400 mb-1">
                                <span class="text-blue-400 font-bold">{{ $act->action }}</span>
                                <span class="text-[10px]">{{ $act->created_at?->diffForHumans() }}</span>
                            </div>
                            <div class="text-slate-300 font-sans text-xs">{{ $act->remarks }}</div>
                            <div class="text-[10px] text-slate-500 mt-1">Updated by {{ $act->user?->name ?? 'System' }}</div>
                        </div>
                    @empty
                        <div class="text-slate-500 text-center py-2">No updates recorded yet.</div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Right: NOC / Engineer Action Panel (1 Col) -->
        <div class="space-y-6">
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <h3 class="font-bold text-white text-base border-b border-slate-800 pb-3">Update Ticket & Assign</h3>

                <form method="POST" action="{{ route('tickets.update-status', $ticket) }}" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-300 uppercase mb-1">Assigned NOC / Field Engineer</label>
                        <select name="assigned_to" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white">
                            <option value="">-- Unassigned --</option>
                            @foreach($engineers as $eng)
                                <option value="{{ $eng->id }}" {{ $ticket->assigned_to == $eng->id ? 'selected' : '' }}>
                                    {{ $eng->name }} ({{ strtoupper($eng->role->name) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 uppercase mb-1">Update Status *</label>
                        <select name="status" required class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white">
                            <option value="OPEN" {{ $ticket->status === 'OPEN' ? 'selected' : '' }}>OPEN</option>
                            <option value="WORKING" {{ $ticket->status === 'WORKING' ? 'selected' : '' }}>WORKING (Under Splicing/Inspection)</option>
                            <option value="RESOLVED" {{ $ticket->status === 'RESOLVED' ? 'selected' : '' }}>RESOLVED (Restored to SLA)</option>
                            <option value="CLOSED" {{ $ticket->status === 'CLOSED' ? 'selected' : '' }}>CLOSED (Customer Verified)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 uppercase mb-1">Work Log Remarks</label>
                        <textarea name="remarks" rows="3" placeholder="Spliced fiber cut at Dhanmondi joint box #14. Rx power restored to -15.2 dBm..."
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl shadow-lg shadow-rose-600/30 transition-all">
                        Update Status & Remarks &rarr;
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection
