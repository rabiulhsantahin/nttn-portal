@extends('layouts.app')

@section('title', 'User & Role Access Management')

@section('content')
<div class="space-y-6">

    <!-- Top Action & Title Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-purple-600/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <span>User & Role Access Management</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1">Super Administrator command center: provision user accounts, set credentials, assign roles, and audit access.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('users.create') }}" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-semibold text-xs tracking-wider shadow-lg shadow-purple-600/30 flex items-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add New User</span>
            </a>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4 shadow-lg">
            <div class="text-[11px] font-mono uppercase text-slate-400 font-semibold">Total Accounts</div>
            <div class="text-2xl font-extrabold text-white mt-1">{{ $metrics['total_users'] }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Registered users</div>
        </div>
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4 shadow-lg">
            <div class="text-[11px] font-mono uppercase text-emerald-400 font-semibold">Active Status</div>
            <div class="text-2xl font-extrabold text-emerald-400 mt-1">{{ $metrics['active_users'] }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Login enabled</div>
        </div>
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4 shadow-lg">
            <div class="text-[11px] font-mono uppercase text-purple-400 font-semibold">Super Admins</div>
            <div class="text-2xl font-extrabold text-purple-400 mt-1">{{ $metrics['super_admins'] }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Root administrators</div>
        </div>
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4 shadow-lg">
            <div class="text-[11px] font-mono uppercase text-blue-400 font-semibold">Resellers / ISPs</div>
            <div class="text-2xl font-extrabold text-blue-400 mt-1">{{ $metrics['resellers'] }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Telecom partners</div>
        </div>
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4 shadow-lg">
            <div class="text-[11px] font-mono uppercase text-cyan-400 font-semibold">Ops Engineers</div>
            <div class="text-2xl font-extrabold text-cyan-400 mt-1">{{ $metrics['engineers'] }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Tx & NOC Teams</div>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4 shadow-lg">
        <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, phone, or ISP company..."
                    class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
            </div>

            <div>
                <select name="role" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-purple-500">
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>{{ strtoupper($role->name) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <select name="status" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-purple-500">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-semibold transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'role', 'status']))
                    <a href="{{ route('users.index') }}" class="px-3 py-2 text-slate-400 hover:text-white text-xs">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-slate-400 font-mono uppercase text-[10px] tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">User Details</th>
                        <th class="py-3 px-4">Assigned Role</th>
                        <th class="py-3 px-4">Reseller / Profile</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Registered</th>
                        <th class="py-3 px-4 text-right">Super Admin Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse($users as $u)
                        @php
                            $roleColors = [
                                'super_admin' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
                                'md' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                'reseller' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                'transmission' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
                                'noc' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                'billing' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                            ];
                            $badge = $roleColors[$u->role->name] ?? 'bg-slate-700 text-slate-300';
                            $isSelf = ($u->id === auth()->id());
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition-colors {{ $isSelf ? 'bg-purple-950/10' : '' }}">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-slate-800 to-slate-700 border border-slate-700 flex items-center justify-center font-bold text-white text-xs shadow">
                                        {{ substr($u->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-white flex items-center gap-2">
                                            <span>{{ $u->name }}</span>
                                            @if($isSelf)
                                                <span class="text-[9px] bg-purple-500/20 text-purple-300 px-1.5 py-0.2 rounded font-mono">YOU</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ $u->email }}</div>
                                        @if($u->phone)
                                            <div class="text-[10px] text-slate-500 font-mono">{{ $u->phone }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="py-3 px-4">
                                <span class="px-2.5 py-1 rounded-full font-bold uppercase tracking-wider text-[10px] border {{ $badge }}">
                                    {{ $u->role->name }}
                                </span>
                            </td>

                            <td class="py-3 px-4">
                                @if($u->reseller)
                                    <div>
                                        <div class="font-medium text-slate-200">{{ $u->reseller->company_name }}</div>
                                        <div class="text-[10px] text-cyan-400 font-mono">{{ $u->reseller->reseller_code }}</div>
                                    </div>
                                @else
                                    <span class="text-slate-500 font-mono text-[11px]">—</span>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                @if($u->is_active)
                                    <span class="inline-flex items-center gap-1.5 text-emerald-400 text-xs font-medium">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-rose-400 text-xs font-medium">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4 font-mono text-slate-400 text-[11px]">
                                {{ $u->created_at?->format('M d, Y') ?? '—' }}
                            </td>

                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if(!$isSelf)
                                    <!-- Login As / Impersonate Button -->
                                    <form method="POST" action="{{ route('users.impersonate', $u) }}" class="inline">
                                        @csrf
                                        <button type="submit" title="Login as this user"
                                            class="px-2.5 py-1 bg-cyan-600/10 hover:bg-cyan-600 border border-cyan-500/20 text-cyan-300 hover:text-white rounded-lg text-[11px] font-semibold transition-all flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                            <span>Login As</span>
                                        </button>
                                    </form>
                                    @endif

                                    <!-- Edit User Button -->
                                    <a href="{{ route('users.edit', $u) }}"
                                        class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-[11px] font-semibold transition-all flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Edit</span>
                                    </a>

                                    @if(!$isSelf)
                                    <!-- Delete User Button -->
                                    <form method="POST" action="{{ route('users.destroy', $u) }}" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete user {{ $u->name }} ({{ $u->email }})?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete User"
                                            class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-slate-500">
                                No user accounts match the search or filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-950/60">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
