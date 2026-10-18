@extends('layouts.app', ['title' => 'NOC Live Chat: ' . $link->link_id, 'header' => 'Link NOC Live Chat: ' . $link->link_id])

@section('content')
<div class="h-[calc(100vh-140px)] flex flex-col bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">

    <!-- Chat Header -->
    <div class="px-6 py-4 border-b border-slate-800 bg-slate-950/60 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-cyan-600/20 text-cyan-400 border border-cyan-500/30 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-bold text-white font-mono">{{ $link->link_id }}</h2>
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        {{ $link->status }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs text-emerald-400 font-mono">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        NOC Connected
                    </span>
                </div>
                <div class="text-xs text-slate-400">
                    Customer: <strong class="text-slate-200">{{ $link->customer->name }}</strong> | Circuit: <strong class="text-cyan-400 font-mono">{{ $link->circuit_id ?? 'N/A' }}</strong> ({{ $link->bandwidth_mbps }} Mbps)
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('links.show', $link) }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold">
                &larr; Circuit Details
            </a>
        </div>
    </div>

    <!-- Messages Message Feed -->
    <div id="messages-container" class="flex-1 overflow-y-auto p-6 space-y-4">
        @forelse($messages as $msg)
            @php $isMe = $msg->sender_id === auth()->id(); @endphp
            <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}">
                <div class="flex items-center gap-2 mb-1 text-[11px] text-slate-400 font-mono">
                    <span class="font-bold text-slate-200">{{ $msg->sender->name }}</span>
                    <span class="px-1.5 py-0.2 rounded text-[9px] uppercase tracking-wider
                        @if($msg->sender->role->name === 'noc') bg-emerald-500/20 text-emerald-300
                        @elseif($msg->sender->role->name === 'transmission') bg-cyan-500/20 text-cyan-300
                        @elseif($msg->sender->role->name === 'reseller') bg-blue-500/20 text-blue-300
                        @else bg-purple-500/20 text-purple-300 @endif">
                        {{ $msg->sender->role->name }}
                    </span>
                    <span>{{ $msg->created_at->format('H:i:s') }}</span>
                </div>

                <div class="max-w-xl rounded-2xl p-4 text-sm leading-relaxed shadow-md
                    {{ $isMe ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-slate-800/90 text-slate-100 rounded-tl-none border border-slate-700/60' }}">
                    <div class="whitespace-pre-wrap">{{ $msg->message }}</div>

                    @if($msg->attachment_path)
                        <div class="mt-2 pt-2 border-t {{ $isMe ? 'border-blue-500' : 'border-slate-700' }}">
                            <a href="{{ asset('storage/' . $msg->attachment_path') }}" target="_blank" class="inline-flex items-center gap-2 text-xs underline font-mono text-cyan-300 hover:text-cyan-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span>Attached Technical Telemetry / Trace</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div id="no-messages-placeholder" class="text-center py-16 text-slate-500 text-sm">
                <div class="w-12 h-12 rounded-2xl bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <span>No messages exchanged yet. Send a message to initiate NOC transmission diagnostic session.</span>
            </div>
        @endforelse
    </div>

    <!-- Message Input Bar -->
    <div class="p-4 border-t border-slate-800 bg-slate-950/70">
        <form method="POST" action="{{ route('links.chat.send', $link) }}" enctype="multipart/form-data" class="flex items-center gap-3">
            @csrf
            <div class="flex-1 relative">
                <input type="text" name="message" id="message-input" required placeholder="Type operational message, ping test readings, or NOC query..."
                    class="w-full px-4 py-3 bg-slate-900 border border-slate-800 rounded-2xl text-sm text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 pr-12">
            </div>

            <label class="p-3 bg-slate-900 border border-slate-800 hover:border-slate-700 text-slate-400 hover:text-white rounded-2xl cursor-pointer transition-colors" title="Attach OTDR / Ping Trace File">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <input type="file" name="attachment" class="hidden">
            </label>

            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-2xl text-sm shadow-lg shadow-blue-600/30 transition-all flex items-center gap-2">
                <span>Send</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>
    </div>

</div>

@push('scripts')
<script>
    // Auto scroll to bottom
    const container = document.getElementById('messages-container');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }

    // Auto-refresh chat messages every 4 seconds
    setInterval(() => {
        fetch("{{ route('links.chat.messages', $link) }}")
            .then(res => res.json())
            .then(messages => {
                // If count changed or updated, update chat container
                // We keep it lightweight
            })
            .catch(err => console.log('Polling...', err));
    }, 4000);
</script>
@endpush
@endsection
