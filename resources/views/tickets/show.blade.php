@extends('layouts.app', ['title' => 'Ticket ' . $ticket->ticket_no, 'header' => 'Incident Workbench: ' . $ticket->ticket_no])

@section('content')
<div class="space-y-6">

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
                <span class="px-3 py-1 rounded-full text-xs font-mono font-bold
                    @if($ticket->is_customer_verified) bg-emerald-500/20 text-emerald-400 border border-emerald-500/30
                    @elseif($ticket->status === 'RESOLVED') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                    @elseif($ticket->status === 'CLOSED') bg-slate-800 text-slate-300
                    @else bg-rose-500/10 text-rose-400 border border-rose-500/20 @endif">
                    STATUS: {{ $ticket->is_customer_verified ? 'CLOSED (CUSTOMER UP VERIFIED)' : $ticket->status }}
                </span>
            </div>
            <div class="text-xs text-slate-400 mt-2 font-mono">
                Affected Circuit: <a href="{{ route('links.show', $ticket->link) }}" class="text-cyan-400 font-bold hover:underline">{{ $ticket->link->link_id }}</a> (Circuit: <span class="text-slate-300">{{ $ticket->link->circuit_id ?? 'N/A' }}</span>@if(!auth()->user()->isReseller() && $ticket->link->nttn_link_id), NTTN: <span class="text-emerald-400">{{ $ticket->link->nttn_link_id }}</span>@endif) 
                | Customer: <strong class="text-slate-200">{{ $ticket->link->customer->name }}</strong>
                | Logged by: <strong class="text-slate-200">{{ $ticket->creator->name }}</strong> ({{ $ticket->created_at->format('M d, Y H:i') }})
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if(auth()->user()->isSuperAdmin())
                <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete trouble ticket {{ $ticket->ticket_no }}? All message logs and attachments will be deleted.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3.5 py-2 bg-rose-600/10 hover:bg-rose-600 text-rose-400 hover:text-white border border-rose-500/20 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Delete Ticket</span>
                    </button>
                </form>
            @endif
            <a href="{{ route('links.chat', $ticket->link) }}" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <span>Link NOC Chat</span>
            </a>
            <a href="{{ route('tickets.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold">
                &larr; Back
            </a>
        </div>
    </div>

    <!-- Customer UP Verification Banner (Active when ticket is resolved or closed but not yet verified) -->
    @if($ticket->canBeVerified())
        <div class="p-5 rounded-3xl bg-gradient-to-r from-emerald-950/60 via-slate-900 to-slate-900 border border-emerald-500/40 shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 font-bold flex items-center justify-center text-xl">
                    ⚡
                </div>
                <div>
                    <h4 class="font-bold text-white text-sm">Circuit Restored: Verify Customer Connection</h4>
                    <p class="text-xs text-slate-300">NOC has worked on this circuit. If customer confirms connectivity is UP, verify below to permanently finalize and lock this ticket.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('tickets.verify-up', $ticket) }}">
                @csrf
                <button type="submit" onclick="return confirm('Confirm customer connectivity is UP? This will permanently close and archive this ticket.')"
                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center gap-2 whitespace-nowrap">
                    <span>✅ Confirm Customer is UP & Verify Closure &rarr;</span>
                </button>
            </form>
        </div>
    @endif

    <!-- Locked Read-Only Notice (when verified / locked) -->
    @if($ticket->isLocked())
        <div class="p-4 rounded-2xl bg-slate-950/90 border border-emerald-500/30 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-xl">🔒</span>
                <div>
                    <span class="font-bold text-white text-xs">Ticket Closed & Customer Verified (Read-Only Archive):</span>
                    <span class="text-xs text-slate-300"> Customer has confirmed service is UP. No further operations or status edits can be made. Chat and picture history are archived for audit.</span>
                </div>
            </div>
            @if($ticket->verified_at)
                <span class="text-[11px] font-mono text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-lg border border-emerald-500/20 whitespace-nowrap">
                    ✓ Verified: {{ $ticket->verified_at->format('M d, H:i') }}
                </span>
            @endif
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-8">
        
        <!-- Left: Incident Details & Chat Feed (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Description Card -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <h3 class="font-bold text-white text-base border-b border-slate-800 pb-3">Incident Description</h3>
                <div class="text-sm font-semibold text-rose-400 font-mono">{{ $ticket->type }}</div>
                <div class="p-4 bg-slate-950/60 rounded-2xl border border-slate-800 text-sm text-slate-200 leading-relaxed whitespace-pre-wrap">
                    {{ $ticket->description }}
                </div>

                @if($ticket->attachment_path)
                    <div class="pt-3 border-t border-slate-800">
                        <div class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Initial Incident Photo / Telemetry</span>
                        </div>
                        <a href="{{ asset('storage/' . $ticket->attachment_path) }}" target="_blank" class="inline-block group">
                            <img src="{{ asset('storage/' . $ticket->attachment_path) }}" alt="Incident Attachment" class="max-h-72 rounded-2xl border border-slate-700/80 object-cover shadow-lg group-hover:opacity-90 transition-opacity">
                            <span class="text-[11px] text-cyan-400 font-mono mt-1 block group-hover:underline">Click to view full image &rarr;</span>
                        </a>
                    </div>
                @endif

                <div class="flex items-center gap-4 text-xs font-mono pt-2">
                    @if($ticket->resolved_at)
                        <span class="text-emerald-400">&check; Resolved: {{ $ticket->resolved_at->format('M d, H:i') }}</span>
                    @endif
                    @if($ticket->closed_at)
                        <span class="text-slate-400">&check; Closed: {{ $ticket->closed_at->format('M d, H:i') }}</span>
                    @endif
                </div>
            </div>

            <!-- Trouble Ticket Chat & Picture History -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <h3 class="font-bold text-white text-base">Trouble Ticket Chat & Picture History</h3>
                    </div>
                    <span class="text-xs font-mono text-slate-400">({{ $ticket->messages->count() }} updates)</span>
                </div>

                <!-- Messages Feed -->
                <div class="p-6 space-y-4 max-h-[500px] overflow-y-auto">
                    @forelse($ticket->messages as $msg)
                        @php $isMe = $msg->user_id === auth()->id(); @endphp
                        <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}">
                            <div class="flex items-center gap-2 mb-1 text-[11px] text-slate-400 font-mono">
                                <span class="font-bold text-slate-200">{{ $msg->user->name }}</span>
                                <span class="px-1.5 py-0.2 rounded text-[9px] uppercase tracking-wider
                                    @if($msg->user->role?->name === 'noc') bg-emerald-500/20 text-emerald-300
                                    @elseif($msg->user->role?->name === 'transmission') bg-cyan-500/20 text-cyan-300
                                    @elseif($msg->user->role?->name === 'reseller') bg-blue-500/20 text-blue-300
                                    @else bg-purple-500/20 text-purple-300 @endif">
                                    {{ $msg->user->role?->name ?? 'User' }}
                                </span>
                                <span>{{ $msg->created_at->format('M d, H:i') }}</span>
                            </div>

                            <div class="max-w-xl rounded-2xl p-4 text-xs leading-relaxed shadow-md
                                {{ $isMe ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-slate-950 text-slate-200 rounded-tl-none border border-slate-800' }}">
                                @if($msg->message)
                                    <div class="whitespace-pre-wrap">{{ $msg->message }}</div>
                                @endif

                                @if($msg->attachment_path)
                                    <div class="mt-2 pt-2 border-t {{ $isMe ? 'border-blue-500' : 'border-slate-800' }}">
                                        <a href="{{ asset('storage/' . $msg->attachment_path) }}" target="_blank" class="block group">
                                            <img src="{{ asset('storage/' . $msg->attachment_path) }}" alt="Shared Photo" class="max-h-56 rounded-xl border {{ $isMe ? 'border-blue-400' : 'border-slate-700' }} object-cover">
                                            <span class="text-[10px] underline font-mono text-cyan-300 group-hover:text-cyan-200 mt-1 inline-block">View full photo &rarr;</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-slate-500 text-xs">
                            No messages or pictures shared in this ticket thread yet.
                        </div>
                    @endforelse
                </div>

                <!-- Post message / picture input -->
                <div class="p-4 border-t border-slate-800 bg-slate-950/60">
                    @if($ticket->isLocked())
                        <div class="p-3 bg-slate-900 border border-slate-800 rounded-xl text-center text-xs text-slate-400 font-mono flex items-center justify-center gap-2">
                            <span>🔒</span>
                            <span>Ticket is closed & customer verified. Chat history is archived in read-only mode.</span>
                        </div>
                    @else
                        <form method="POST" action="{{ route('tickets.messages.send', $ticket) }}" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <div class="flex items-center gap-2">
                                <input type="text" name="message" placeholder="Type an update, diagnostic note, or description..."
                                    class="flex-1 px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500">

                                <label class="px-3 py-2.5 bg-slate-900 border border-slate-800 hover:border-slate-700 text-slate-400 hover:text-white rounded-xl cursor-pointer transition-colors flex items-center gap-1.5 text-xs font-semibold" title="Attach picture / screenshot">
                                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span id="chat-file-chosen" class="hidden sm:inline">Attach Pic</span>
                                    <input type="file" name="attachment" accept="image/*" class="hidden" onchange="document.getElementById('chat-file-chosen').innerText = this.files[0]?.name?.substring(0, 15) || 'Attach Pic';">
                                </label>

                                <button type="submit" class="px-4 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-xl text-xs shadow-lg shadow-cyan-600/30 transition-all flex items-center gap-1.5 whitespace-nowrap">
                                    <span>Send</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Activity Logs on this ticket -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                    <h3 class="font-bold text-white text-base">Resolution Audit Timeline</h3>
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

        <!-- Right: NOC / Engineer Action Panel OR Locked Read-Only Notice (1 Col) -->
        <div class="space-y-6">

            @if($ticket->isLocked())
                <!-- Permanently Locked Card -->
                <div class="bg-slate-900/80 border border-emerald-500/30 rounded-3xl p-6 shadow-xl text-center space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto text-xl font-bold">
                        🔒
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-base">Ticket Closed & Verified</h4>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                            Customer confirmed circuit is UP. This ticket has been finalized and archived in read-only mode.
                        </p>
                    </div>

                    <div class="p-4 bg-slate-950/60 rounded-2xl border border-slate-800 text-left font-mono text-xs space-y-2 text-slate-300">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Status:</span>
                            <span class="text-emerald-400 font-bold">CLOSED &check;</span>
                        </div>
                        @if($ticket->verified_at)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Customer UP Verified:</span>
                                <span class="text-slate-200">{{ $ticket->verified_at->format('M d, Y H:i') }}</span>
                            </div>
                        @endif
                        @if($ticket->verifier)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Verified By:</span>
                                <span class="text-slate-200">{{ $ticket->verifier->name }}</span>
                            </div>
                        @endif
                        @if($ticket->closed_at)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Closed At:</span>
                                <span class="text-slate-200">{{ $ticket->closed_at->format('M d, Y H:i') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @elseif(auth()->user()->isReseller())
                <!-- Reseller Action Panel: Only Open and Close Verify Options -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                    <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
                        <h3 class="font-bold text-white text-base">Subscriber Ticket Action</h3>
                        <span class="text-[10px] font-mono text-cyan-400 bg-cyan-500/10 px-2 py-0.5 rounded border border-cyan-500/20">Client Self-Service</span>
                    </div>

                    <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800 text-xs space-y-1.5 font-mono">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Handling Team:</span>
                            <span class="text-slate-200 font-bold">{{ $ticket->assignedEngineer->name ?? 'NOC Engineering Queue' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Current Status:</span>
                            <span class="text-rose-400 font-bold">{{ $ticket->status }}</span>
                        </div>
                    </div>

                    @if($ticket->canBeVerified())
                        <div class="p-3.5 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-xs space-y-3">
                            <div class="text-emerald-300 font-semibold flex items-center gap-1.5">
                                <span>⚡</span>
                                <span>NOC has restored circuit connectivity.</span>
                            </div>
                            <p class="text-slate-300 text-[11px] leading-relaxed">
                                Please verify customer connectivity is UP to finalize and close this ticket.
                            </p>
                            <form method="POST" action="{{ route('tickets.verify-up', $ticket) }}">
                                @csrf
                                <button type="submit" onclick="return confirm('Confirm customer connectivity is UP? This will permanently close and archive this ticket.')"
                                    class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition-all text-xs flex items-center justify-center gap-2">
                                    <span>✅ Customer UP: Verify & Close Ticket &rarr;</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <form method="POST" action="{{ route('tickets.update-status', $ticket) }}" enctype="multipart/form-data" class="space-y-4 text-xs">
                            @csrf
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Ticket Action *</label>
                                <select name="status" required class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                                    <option value="OPEN" {{ $ticket->status === 'OPEN' ? 'selected' : '' }}>OPEN (Issue Ongoing / Keep Under Investigation)</option>
                                    <option value="CLOSED" {{ $ticket->status === 'CLOSED' ? 'selected' : '' }}>CLOSED (Customer UP / Verify & Close)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Customer Remarks / Observations</label>
                                <textarea name="remarks" rows="3" placeholder="Provide feedback or observation to NOC team..."
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white"></textarea>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Attach Photo / Screenshot (Optional)</label>
                                <label class="w-full flex items-center gap-2 px-3 py-2 bg-slate-950 border border-slate-700 hover:border-slate-600 rounded-xl cursor-pointer text-slate-400 hover:text-white transition-colors">
                                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span id="reseller-pic-name" class="text-xs truncate">Upload pic...</span>
                                    <input type="file" name="attachment" accept="image/*" class="hidden" onchange="document.getElementById('reseller-pic-name').innerText = this.files[0]?.name || 'Upload pic...';">
                                </label>
                            </div>

                            <button type="submit" class="w-full py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl shadow-lg shadow-rose-600/30 transition-all">
                                Update Ticket &rarr;
                            </button>
                        </form>
                    @endif
                </div>
            @else
                <!-- Active Action Form for NOC, Transmission, Admin, MD (All Options) -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                    <h3 class="font-bold text-white text-base border-b border-slate-800 pb-3">Update Ticket & Assign</h3>

                    <form method="POST" action="{{ route('tickets.update-status', $ticket) }}" enctype="multipart/form-data" class="space-y-4 text-xs">
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
                                <option value="CLOSED" {{ $ticket->status === 'CLOSED' ? 'selected' : '' }}>CLOSED (Ready for Customer UP Verification)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">Work Log Remarks</label>
                            <textarea name="remarks" rows="3" placeholder="Spliced fiber cut at Dhanmondi joint box #14. Rx power restored to -15.2 dBm..."
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white"></textarea>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">Attach Splicing / Field Photo (Optional)</label>
                            <label class="w-full flex items-center gap-2 px-3 py-2 bg-slate-950 border border-slate-700 hover:border-slate-600 rounded-xl cursor-pointer text-slate-400 hover:text-white transition-colors">
                                <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span id="status-pic-name" class="text-xs truncate">Upload pic...</span>
                                <input type="file" name="attachment" accept="image/*" class="hidden" onchange="document.getElementById('status-pic-name').innerText = this.files[0]?.name || 'Upload pic...';">
                            </label>
                        </div>

                        <button type="submit" class="w-full py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl shadow-lg shadow-rose-600/30 transition-all">
                            Update Status & Remarks &rarr;
                        </button>
                    </form>
                </div>
            @endif

        </div>

    </div>

</div>
@endsection
