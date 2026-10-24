@extends('layouts.app', ['title' => 'Disaster Recovery & Database Backups', 'header' => 'Disaster Recovery & Automated Database Backups'])

@section('content')
<div class="space-y-8">

    <!-- Top Action & Overview Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl backdrop-blur-xl">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-600 via-teal-600 to-cyan-400 flex items-center justify-center text-white shadow-lg shadow-emerald-600/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                </div>
                <div>
                    <h2 class="text-2xl font-extrabold text-white tracking-tight">Database Disaster Recovery &amp; Email Backups</h2>
                    <div class="text-xs text-emerald-400 font-mono mt-0.5">Automated Daily Protection &bull; Zero Data Loss Protocol</div>
                </div>
            </div>
            <p class="text-xs text-slate-400 mt-2 max-w-2xl leading-relaxed">
                সার্ভার অথবা VPS ক্র্যাশ বা কোনো সমস্যা হলে ডাটা রিস্টোর করার জন্য প্রতিদিন স্বয়ংক্রিয়ভাবে ইমেইলে ডাটাবেজের সম্পূর্ণ ব্যাকআপ পাঠানো হয়। এখান থেকে তাৎক্ষণিক ব্যাকআপ তৈরি, ডাউনলোড এবং রিস্টোর গাইডলাইন পরিচালনা করা যাবে।
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Quick Download Button -->
            <form method="POST" action="{{ route('backups.store') }}">
                @csrf
                <input type="hidden" name="download" value="1">
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs tracking-wider border border-slate-700 flex items-center gap-2 transition-all shadow-md cursor-pointer">
                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Download .SQL.GZ</span>
                </button>
            </form>

            <!-- Quick Email Dispatch Button -->
            <form method="POST" action="{{ route('backups.email') }}">
                @csrf
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-semibold text-xs tracking-wider shadow-lg shadow-emerald-600/30 flex items-center gap-2 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>Email Backup Now</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <div class="text-[11px] font-mono uppercase text-slate-400 font-semibold">Total Archives</div>
            <div class="text-2xl font-extrabold text-white mt-1">{{ count($backups) }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Stored on disk</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <div class="text-[11px] font-mono uppercase text-cyan-400 font-semibold">Disk Storage Used</div>
            <div class="text-2xl font-extrabold text-cyan-400 mt-1">{{ $totalStorageFormatted }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Gzip compressed</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <div class="text-[11px] font-mono uppercase text-emerald-400 font-semibold">Daily Cron Schedule</div>
            <div class="text-lg font-bold text-emerald-400 mt-1 font-mono">02:00 AM UTC</div>
            <div class="text-[10px] text-slate-500 mt-1">{{ ($settings['enabled'] ?? true) ? 'Active & Running' : 'Disabled' }}</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <div class="text-[11px] font-mono uppercase text-purple-400 font-semibold">Last Backup Status</div>
            <div class="text-sm font-bold text-white mt-2 truncate font-mono">
                @if(($settings['last_status'] ?? '') === 'SUCCESS')
                    <span class="text-emerald-400">✓ SUCCESS</span>
                @elseif(($settings['last_status'] ?? '') === 'EMAIL_FAILED')
                    <span class="text-rose-400">⚠ FAILED</span>
                @else
                    <span class="text-slate-400">N/A</span>
                @endif
            </div>
            <div class="text-[10px] text-slate-500 mt-1 truncate">{{ $settings['last_backup_at'] ?? 'Never' }}</div>
        </div>
    </div>

    <!-- Two-Column Section: Immediate Actions & Daily Settings -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left: Daily Email Automation Settings (7 cols) -->
        <div class="lg:col-span-7 bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-7 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800/80 mb-5">
                <div>
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Daily Email Backup Configuration</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Configure which email receives daily database dumps and retention limits.</p>
                </div>
                <span class="text-xs font-mono px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    Cron Active
                </span>
            </div>

            <form method="POST" action="{{ route('backups.settings') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Backup Recipient Email Address (যে ইমেইলে ব্যাকআপ যাবে)
                    </label>
                    <input type="email" name="email" value="{{ old('email', $settings['email'] ?? auth()->user()->email) }}" required
                        placeholder="admin@nttn.test"
                        class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 font-mono">
                    <p class="text-[11px] text-slate-500 mt-1">This email will receive the compressed <code>.sql.gz</code> attachment every day at 02:00 AM UTC.</p>
                </div>

                <div class="grid sm:grid-cols-2 gap-4 pt-1">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Retention Days (সার্ভারে কতদিন ফাইল থাকবে)
                        </label>
                        <input type="number" name="keep_days" value="{{ old('keep_days', $settings['keep_days'] ?? 14) }}" min="1" max="365" required
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-mono">
                        <p class="text-[11px] text-slate-500 mt-1">Old backups beyond this threshold are automatically pruned from VPS disk.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Automated Schedule Status
                        </label>
                        <div class="flex items-center h-10">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="enabled" value="1" {{ ($settings['enabled'] ?? true) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-0">
                                <span class="text-xs text-slate-200 font-semibold">Enable Daily Auto Backup</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between">
                    <span class="text-[11px] text-slate-500 font-mono">
                        Mailer Transport: <strong class="text-slate-300">{{ $mailConfig['mailer'] }}</strong>
                    </span>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition-all cursor-pointer">
                        Save Backup Configuration
                    </button>
                </div>
            </form>
        </div>

        <!-- Right: Dispatch to Custom Email (5 cols) -->
        <div class="lg:col-span-5 bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-7 shadow-xl flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-white flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span>Instant Backup Dispatch</span>
                </h3>
                <p class="text-xs text-slate-400 mb-4">Send a full database snapshot directly to an alternate address right now.</p>

                <form method="POST" action="{{ route('backups.email') }}" class="space-y-3.5">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Send To Custom Email (নির্দিষ্ট ইমেইল)
                        </label>
                        <input type="email" name="email" value="{{ auth()->user()->email }}" required
                            placeholder="your.email@example.com"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500 font-mono">
                    </div>

                    <button type="submit" class="w-full py-3 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl text-xs shadow-lg shadow-cyan-600/25 transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Generate &amp; Dispatch to Email</span>
                    </button>
                </form>
            </div>

            <div class="mt-6 p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800 text-[11px] text-slate-400 space-y-1">
                <div class="font-bold text-slate-300">Terminal CLI Backup:</div>
                <div class="font-mono text-cyan-400 text-[10px]">php artisan backup:database --email=target@mail.com</div>
            </div>
        </div>

    </div>

    <!-- Section: Existing Backup Archive Ledger Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
            <div>
                <h3 class="font-bold text-white text-base">Disaster Recovery Archive Ledger</h3>
                <p class="text-xs text-slate-400">Available database dumps stored securely in <code>storage/app/backups/</code></p>
            </div>
            <span class="text-xs text-slate-400 font-mono">{{ count($backups) }} files present</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-slate-400 font-mono uppercase text-[10px] tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-6">Archive File Name</th>
                        <th class="py-3 px-6">Compressed Size</th>
                        <th class="py-3 px-6">Created Timestamp</th>
                        <th class="py-3 px-6">Type / Format</th>
                        <th class="py-3 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse($backups as $b)
                        <tr class="hover:bg-slate-800/40 transition-colors font-medium">
                            <td class="py-3.5 px-6 font-mono text-cyan-400 font-bold">
                                {{ $b['filename'] }}
                            </td>
                            <td class="py-3.5 px-6 font-mono text-emerald-400">
                                {{ $b['size_formatted'] }}
                            </td>
                            <td class="py-3.5 px-6 font-mono text-slate-400">
                                {{ $b['created_at']->format('Y-m-d H:i:s') }}
                                <span class="text-[10px] text-slate-500">({{ $b['created_at']->diffForHumans() }})</span>
                            </td>
                            <td class="py-3.5 px-6">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                    {{ $b['is_compressed'] ? 'GZIP SQL (.sql.gz)' : 'PLAIN SQL' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Download Button -->
                                    <a href="{{ route('backups.download', $b['filename']) }}" 
                                        class="px-2.5 py-1 bg-cyan-600/10 hover:bg-cyan-600 text-cyan-300 hover:text-white border border-cyan-500/20 rounded-lg text-[11px] font-semibold transition-all flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        <span>Download</span>
                                    </a>

                                    <!-- Send to Email Button -->
                                    <form method="POST" action="{{ route('backups.email') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="filename" value="{{ $b['filename'] }}">
                                        <button type="submit" title="Send this file to configured backup email"
                                            class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-[11px] font-semibold transition-all flex items-center gap-1 cursor-pointer">
                                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            <span>Email</span>
                                        </button>
                                    </form>

                                    <!-- Delete Button -->
                                    <form method="POST" action="{{ route('backups.destroy', $b['filename']) }}" class="inline" onsubmit="return confirm('Delete this backup file permanently from the server?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete Archive"
                                            class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                                No backup archives generated yet. Click "Download .SQL.GZ" or "Email Backup Now" above to generate your first snapshot.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Disaster Recovery Guide & Restoration Playbook Card -->
    <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950/40 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-4">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-white">Disaster Recovery &amp; Server Migration Playbook (রিস্টোর গাইড)</h3>
                <p class="text-xs text-slate-400">Follow these steps if your VPS experiences a complete failure or if you migrate to a new server:</p>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4 pt-2">
            <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-2">
                <div class="text-xs font-bold text-emerald-400 font-mono">ধাপ ১: নতুন সার্ভারে ডাটাবেজ তৈরি করুন</div>
                <p class="text-xs text-slate-400">নতুন যেকোনো সার্ভার বা VPS-এ MariaDB/MySQL ইন্সটল করে ডাটাবেজ ক্রিয়েট করুন:</p>
                <div class="p-2.5 rounded-xl bg-slate-900 text-xs font-mono text-cyan-300 border border-slate-800 select-all">
                    mariadb -e "CREATE DATABASE nttn_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-2">
                <div class="text-xs font-bold text-cyan-400 font-mono">ধাপ ২: ইমেইলের ব্যাকআপ ফাইল ইমপোর্ট করুন</div>
                <p class="text-xs text-slate-400">ইমেইলে আসা <code>.sql.gz</code> ফাইলটি আনজিপ করে সরাসরি ইমপোর্ট করুন:</p>
                <div class="p-2.5 rounded-xl bg-slate-900 text-xs font-mono text-cyan-300 border border-slate-800 select-all">
                    gunzip -c backup_file.sql.gz | mariadb -u root -p nttn_portal
                </div>
            </div>
        </div>

        <div class="pt-2 text-xs text-slate-400 leading-relaxed">
            <strong class="text-white">ফলাফল:</strong> সম্পূর্ণ ইউজার একাউন্ট, রেসেলার, কাস্টমার, সার্ভে, অপটিক্যাল লিংক, বিলিং রেকর্ড ও টিকিট নিমেষেই ১০০% রিস্টোর হয়ে যাবে।
        </div>
    </div>

</div>
@endsection
