@extends('layouts.app', ['title' => 'Bandwidth Requests', 'header' => 'Bandwidth Scaling: Upgrade & Downgrade Queue'])

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Bandwidth Modification Requests</h2>
            <p class="text-xs text-slate-400 mt-1">Review bandwidth expansion or optimization requests, manage commercial adjustments, and provision router rate-limiters.</p>
        </div>
    </div>

    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Request #</th>
                        <th class="px-6 py-4">Circuit</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4">Current &rarr; Requested</th>
                        <th class="px-6 py-4">Reason</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($requests as $sr)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-indigo-400">
                                {{ $sr->request_no }}
                                <div class="text-[11px] text-slate-500 font-mono">{{ $sr->created_at->format('M d, H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-cyan-400">
                                <a href="{{ route('links.show', $sr->link) }}" class="hover:underline">
                                    {{ $sr->link->link_id }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-white font-semibold">
                                {{ $sr->link->customer->name }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded text-xs font-mono font-bold {{ $sr->type === 'UPGRADE' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                                    {{ $sr->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                {{ $sr->old_bandwidth_mbps }} Mbps &rarr; <strong class="text-cyan-400">{{ $sr->requested_bandwidth_mbps }} Mbps</strong>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-300 max-w-xs truncate">
                                {{ $sr->reason ?? 'No remarks' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold
                                    @if($sr->status === 'COMPLETED') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                    @elseif($sr->status === 'APPROVED') bg-blue-500/10 text-blue-400 border border-blue-500/20
                                    @elseif($sr->status === 'REJECTED') bg-rose-500/10 text-rose-400 border border-rose-500/20
                                    @else bg-amber-500/10 text-amber-400 border border-amber-500/20 @endif">
                                    {{ $sr->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($sr->status === 'PENDING' && (auth()->user()->isMd() || auth()->user()->isSuperAdmin()))
                                        <form method="POST" action="{{ route('service-requests.decide', $sr) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow">
                                                Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('service-requests.decide', $sr) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="px-3 py-1 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-bold shadow">
                                                Reject
                                            </button>
                                        </form>
                                    @elseif($sr->status === 'APPROVED' && (auth()->user()->isNoc() || auth()->user()->isSuperAdmin()))
                                        <form method="POST" action="{{ route('service-requests.decide', $sr) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="implement">
                                            <button type="submit" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-lg text-xs font-bold shadow">
                                                Re-provision Policer &rarr;
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-500 font-mono">No action needed</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-slate-500">
                                No bandwidth change requests in queue.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
