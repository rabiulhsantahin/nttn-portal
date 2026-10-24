@extends('layouts.app', ['title' => 'Active Circuits', 'header' => 'Master Inventory of Optical Transmission Circuits'])

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('links.index') }}" class="flex items-center gap-3 flex-1 max-w-xl">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Link ID, Circuit ID, Customer..."
                class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
            
            <select name="status" onchange="this.form.submit()" class="px-3 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-slate-300 focus:outline-none focus:border-blue-500">
                <option value="">All Statuses</option>
                <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                <option value="SUSPENDED" {{ request('status') === 'SUSPENDED' ? 'selected' : '' }}>Suspended</option>
            </select>

            <button type="submit" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-sm font-semibold transition-colors">
                Filter
            </button>
        </form>
    </div>

    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Link ID</th>
                        <th class="px-6 py-4">Circuit ID</th>
                        <th class="px-6 py-4">Customer & Location</th>
                        <th class="px-6 py-4">Bandwidth</th>
                        <th class="px-6 py-4">NTTN Carrier</th>
                        <th class="px-6 py-4">VLAN / Port</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($links as $link)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-cyan-400">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <a href="{{ route('links.show', $link) }}" class="hover:underline">
                                        {{ $link->link_id }}
                                    </a>
                                    @if($link->is_legacy)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                            LEGACY
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-300">
                                <div>{{ $link->circuit_id ?? 'Provisioning' }}</div>
                                @if(!auth()->user()->isReseller() && $link->nttn_link_id)
                                    <div class="text-[11px] text-cyan-400 font-normal">NTTN: {{ $link->nttn_link_id }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-white font-bold">{{ $link->customer->name }}</div>
                                <div class="text-xs text-slate-400">{{ $link->customer->district ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-white">
                                {{ $link->bandwidth_mbps }} Mbps
                            </td>
                            <td class="px-6 py-4 text-slate-300">
                                {{ $link->nttn_provider ?? 'Direct Ring' }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-400">
                                {{ $link->vlan ?? 'N/A' }} <br>
                                <span class="text-[10px] text-slate-500">{{ $link->port ?? '' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold
                                    @if($link->status === 'ACTIVE') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                    @elseif($link->status === 'SUSPENDED') bg-rose-500/10 text-rose-400 border border-rose-500/20
                                    @else bg-blue-500/10 text-blue-400 border border-blue-500/20 @endif">
                                    {{ $link->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('links.chat', $link) }}" title="Live NOC Chat" class="p-2 rounded-xl bg-cyan-600/20 text-cyan-400 hover:bg-cyan-600/30 border border-cyan-500/30 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                    </a>
                                    <a href="{{ route('links.show', $link) }}" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white transition-colors">
                                        Manage &rarr;
                                    </a>
                                    @if(auth()->user()->isSuperAdmin())
                                        <form method="POST" action="{{ route('links.destroy', $link) }}" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete circuit link {{ $link->link_id }}? All associated billing records, tickets, and NOC logs will be deleted.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Delete Circuit Link" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors cursor-pointer">
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
                                No links registered in the system.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($links->hasPages())
            <div class="px-6 py-4 border-t border-slate-800">
                {{ $links->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
