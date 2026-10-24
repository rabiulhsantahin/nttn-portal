@extends('layouts.app', ['title' => 'NTTN Carrier & Mail Settings', 'header' => 'System & Carrier Mail Settings'])

@section('content')
<div class="space-y-8 max-w-6xl mx-auto">

    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-blue-950/40 via-slate-900 to-slate-900 border border-blue-500/30 rounded-3xl p-6 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-blue-500/20 text-blue-400 font-bold flex items-center justify-center text-xl shadow-inner">
                    📡
                </div>
                <div>
                    <h2 class="text-2xl font-extrabold text-white tracking-tight">NTTN Carrier &amp; Outgoing Mail Settings</h2>
                    <p class="text-xs text-slate-400 mt-1">Configure NTTN company send-mail addresses and outgoing SMTP server parameters.</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tickets.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold">
                &larr; Trouble Tickets
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center gap-3 shadow-lg">
            <span class="text-lg">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm flex items-center gap-3 shadow-lg">
            <span class="text-lg">⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-8">

        <!-- Left: NTTN Carrier Send-Mail Directory -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-6">
            <div class="border-b border-slate-800 pb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🏢</span>
                        <span>NTTN Provider Send-Mail Directory</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                        Set the destination NOC email address for each NTTN Company. When creating a ticket, selecting a company will auto-fill its configured mail address.
                    </p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    Carrier NOC
                </span>
            </div>

            <form method="POST" action="{{ route('admin.mail-settings.providers') }}" class="space-y-4">
                @csrf
                <div class="space-y-3" id="provider-rows-container">
                    @php $idx = 0; @endphp
                    @foreach($providers as $name => $email)
                        <div class="p-4 bg-slate-950/60 border border-slate-800/90 rounded-2xl space-y-2 provider-row">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex-1">
                                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Company / Panel Name</label>
                                    <input type="text" name="providers[{{ $idx }}][name]" value="{{ $name }}" required
                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-blue-500">
                                </div>
                                @if(!in_array($name, ['Summit Communications', 'Fiber@Home', 'Bahon Limited', 'Direct Ring Core']))
                                    <button type="button" onclick="this.closest('.provider-row').remove()" class="text-rose-400 hover:text-rose-300 text-xs px-2 py-1 mt-5 rounded bg-rose-500/10 border border-rose-500/20">
                                        Delete
                                    </button>
                                @endif
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">
                                    Send Mail Address (Carrier NOC)
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </div>
                                    <input type="text" name="providers[{{ $idx }}][email]" value="{{ $email }}" placeholder="noc@company.com, support@company.com"
                                        class="w-full pl-9 pr-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-mono text-cyan-300 focus:outline-none focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                        @php $idx++; @endphp
                    @endforeach
                </div>

                <div class="pt-2 flex items-center justify-between gap-3">
                    <button type="button" onclick="addNewProviderRow()" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold flex items-center gap-1.5 transition-colors">
                        <span>+ Add Another Provider</span>
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-600/30 transition-all">
                        Save Carrier Mail Addresses &rarr;
                    </button>
                </div>
            </form>
        </div>

        <!-- Right: Outgoing SMTP Mail Server Configuration -->
        <div class="space-y-6">

            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-6">
                <div class="border-b border-slate-800 pb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <span>⚙️</span>
                            <span>Outgoing Mail Server (SMTP) Settings</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                            Configure SMTP or local mail transfer so the portal can dispatch real emails to Gmail, carrier NOCs, and system backups.
                        </p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold 
                        @if($smtpConfig['mailer'] === 'smtp') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                        @elseif($smtpConfig['mailer'] === 'sendmail') bg-cyan-500/10 text-cyan-400 border border-cyan-500/20
                        @else bg-amber-500/10 text-amber-400 border border-amber-500/20 @endif">
                        Driver: {{ strtoupper($smtpConfig['mailer']) }}
                    </span>
                </div>

                <form method="POST" action="{{ route('admin.mail-settings.smtp') }}" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-300 uppercase mb-1">Mail Driver / Transfer Agent *</label>
                        <select name="mail_mailer" required class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono" onchange="toggleSmtpFields(this.value)">
                            <option value="smtp" {{ $smtpConfig['mailer'] === 'smtp' ? 'selected' : '' }}>SMTP (Recommended for Gmail / Office365 / cPanel / Mail Server)</option>
                            <option value="sendmail" {{ $smtpConfig['mailer'] === 'sendmail' ? 'selected' : '' }}>Sendmail / Postfix (Local Server Mail Agent)</option>
                            <option value="log" {{ $smtpConfig['mailer'] === 'log' ? 'selected' : '' }}>Log (Testing only - writes to laravel.log without sending)</option>
                        </select>
                    </div>

                    <div id="smtp-specific-fields" class="space-y-4 {{ $smtpConfig['mailer'] === 'log' ? 'opacity-50 pointer-events-none' : '' }}">
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">SMTP Host</label>
                                <input type="text" name="mail_host" value="{{ $smtpConfig['host'] }}" placeholder="smtp.gmail.com"
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">SMTP Port</label>
                                <input type="number" name="mail_port" value="{{ $smtpConfig['port'] }}" placeholder="587"
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">Encryption</label>
                                <select name="mail_encryption" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                                    <option value="tls" {{ $smtpConfig['encryption'] === 'tls' ? 'selected' : '' }}>TLS (Port 587)</option>
                                    <option value="ssl" {{ $smtpConfig['encryption'] === 'ssl' ? 'selected' : '' }}>SSL (Port 465)</option>
                                    <option value="none" {{ in_array($smtpConfig['encryption'], ['none', 'null', null, '']) ? 'selected' : '' }}>None (Port 25)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 uppercase mb-1">SMTP Username</label>
                                <input type="text" name="mail_username" value="{{ $smtpConfig['username'] }}" placeholder="e.g. noc@nttn-portal.com"
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">
                                SMTP Password / App Password
                                <span class="text-[10px] text-slate-500 lowercase">(leave empty if unchanged)</span>
                            </label>
                            <input type="password" name="mail_password" placeholder="••••••••••••••••"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-3 pt-2 border-t border-slate-800">
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">From Sender Email *</label>
                            <input type="email" name="mail_from_address" value="{{ $smtpConfig['from_address'] }}" required placeholder="noc@company.com"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">From Sender Name *</label>
                            <input type="text" name="mail_from_name" value="{{ $smtpConfig['from_name'] }}" required placeholder="NTTN Portal NOC"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl shadow-lg shadow-blue-600/30 transition-all">
                            Save Outgoing Mail Configuration &rarr;
                        </button>
                    </div>
                </form>
            </div>

            <!-- Live Test Email Dispatcher -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-white flex items-center gap-2">
                            <span>🧪</span>
                            <span>Verify Mail Delivery (Test Dispatch)</span>
                        </h4>
                        <p class="text-xs text-slate-400 mt-0.5">Send an instant test email to verify that your mail server can deliver messages outside.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.mail-settings.test') }}" class="flex items-center gap-3">
                    @csrf
                    <input type="email" name="test_email" required value="{{ auth()->user()->email }}" placeholder="Enter destination email (e.g. nmasum54@gmail.com)..."
                        class="flex-1 px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-emerald-500">
                    <button type="submit" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs shadow-lg shadow-emerald-600/30 transition-all flex items-center gap-1.5 whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <span>Send Test Email &rarr;</span>
                    </button>
                </form>
            </div>

        </div>

    </div>

</div>

<script>
    let providerIndex = {{ $idx }};

    function addNewProviderRow() {
        const container = document.getElementById('provider-rows-container');
        const row = document.createElement('div');
        row.className = 'p-4 bg-slate-950/60 border border-slate-800/90 rounded-2xl space-y-2 provider-row';
        row.innerHTML = `
            <div class="flex items-center justify-between gap-3">
                <div class="flex-1">
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Company / Panel Name</label>
                    <input type="text" name="providers[${providerIndex}][name]" placeholder="e.g. Link3 Technologies" required
                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-blue-500">
                </div>
                <button type="button" onclick="this.closest('.provider-row').remove()" class="text-rose-400 hover:text-rose-300 text-xs px-2 py-1 mt-5 rounded bg-rose-500/10 border border-rose-500/20">
                    Delete
                </button>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">
                    Send Mail Address (Carrier NOC)
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <input type="text" name="providers[${providerIndex}][email]" placeholder="noc@company.com"
                        class="w-full pl-9 pr-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-mono text-cyan-300 focus:outline-none focus:border-blue-500">
                </div>
            </div>
        `;
        container.appendChild(row);
        providerIndex++;
    }

    function toggleSmtpFields(mailer) {
        const fields = document.getElementById('smtp-specific-fields');
        if (mailer === 'log') {
            fields.classList.add('opacity-50', 'pointer-events-none');
        } else {
            fields.classList.remove('opacity-50', 'pointer-events-none');
        }
    }
</script>
@endsection
