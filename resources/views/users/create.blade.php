@extends('layouts.app')

@section('title', 'Create User Account')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('users.index') }}" class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight">Provision New User</h2>
                <p class="text-xs text-slate-400">Set username, password credentials, and assign role authorization.</p>
            </div>
        </div>
    </div>

    <!-- Create Form Card -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-6 backdrop-blur-xl shadow-2xl">
        <form method="POST" action="{{ route('users.store') }}" class="space-y-6">
            @csrf

            <!-- Personal & Login Credentials -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-purple-400 border-b border-slate-800 pb-2">1. Identity & Credentials</h3>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Full Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            placeholder="e.g. Tariqul Islam"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-sm text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                        @error('name') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Email Address (Login Username) *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            placeholder="e.g. tariqul@operator.test"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-sm text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                        @error('email') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Phone Number</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                            placeholder="e.g. +8801700000000"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-sm text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                        @error('phone') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Set Password *</label>
                        <input type="text" name="password" value="{{ old('password', 'password') }}" required
                            placeholder="Minimum 6 characters"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                        <p class="text-[10px] text-slate-500 mt-1">Default suggested: password</p>
                        @error('password') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Role Assignment -->
            <div class="space-y-4 pt-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-purple-400 border-b border-slate-800 pb-2">2. Role & Permissions</h3>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Assign System Role *</label>
                    <div class="grid sm:grid-cols-3 gap-3">
                        @foreach($roles as $role)
                            @php
                                $roleDescriptions = [
                                    'super_admin' => 'Full administrative command',
                                    'md' => 'Commercial sanction & final commission',
                                    'reseller' => 'Partner ISP (order & links)',
                                    'transmission' => 'Feasibility & field optical team',
                                    'noc' => '8-Point SLA & active link tests',
                                    'billing' => 'Monthly billing & tariffs',
                                ];
                            @endphp
                            <label class="relative flex flex-col p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-slate-700 cursor-pointer transition-all has-[:checked]:border-purple-500 has-[:checked]:bg-purple-950/20">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold uppercase font-mono text-white">{{ $role->name }}</span>
                                    <input type="radio" name="role_id" value="{{ $role->id }}" {{ old('role_id') == $role->id || (old('role_id') == null && $role->name === 'reseller') ? 'checked' : '' }}
                                        onchange="toggleResellerFields(this.value, '{{ $role->name }}')"
                                        data-rolename="{{ $role->name }}"
                                        class="text-purple-600 focus:ring-0">
                                </div>
                                <span class="text-[10px] text-slate-400">{{ $roleDescriptions[$role->name] ?? '' }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('role_id') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Reseller Profile Details (Shown when role is reseller) -->
            <div id="reseller-fields" class="space-y-4 pt-2 border-t border-slate-800/80">
                <h3 class="text-xs font-bold uppercase tracking-wider text-blue-400 flex items-center justify-between">
                    <span>3. Reseller / ISP Partner Profile</span>
                    <span class="text-[10px] text-slate-500 lowercase font-normal">(Required when role is Reseller)</span>
                </h3>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">ISP Company Name</label>
                        <input type="text" name="reseller_company_name" value="{{ old('reseller_company_name') }}"
                            placeholder="e.g. Apex Broadband Ltd."
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        @error('reseller_company_name') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Reseller Code (Optional)</label>
                        <input type="text" name="reseller_code" value="{{ old('reseller_code') }}"
                            placeholder="Leave blank for auto RSL-XXXX"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        @error('reseller_code') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Office Address</label>
                    <textarea name="reseller_address" rows="2" placeholder="e.g. House 14, Road 4, Dhanmondi, Dhaka"
                        class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">{{ old('reseller_address') }}</textarea>
                </div>
            </div>

            <!-- Active Status -->
            <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-white uppercase tracking-wider">Account Active Status</div>
                    <div class="text-[11px] text-slate-400">Allow this user to sign in to the portal with their credentials.</div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                </label>
            </div>

            <!-- Submit Buttons -->
            <div class="pt-4 flex items-center justify-end gap-3">
                <a href="{{ route('users.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-semibold text-xs tracking-wider shadow-lg shadow-purple-600/30 transition-all">
                    Create User & Set Role
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    function toggleResellerFields(roleId, roleName) {
        const resellerDiv = document.getElementById('reseller-fields');
        if (roleName === 'reseller') {
            resellerDiv.style.display = 'block';
        } else {
            resellerDiv.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const checkedRadio = document.querySelector('input[name=role_id]:checked');
        if (checkedRadio) {
            toggleResellerFields(checkedRadio.value, checkedRadio.dataset.rolename);
        }
    });
</script>
@endsection
