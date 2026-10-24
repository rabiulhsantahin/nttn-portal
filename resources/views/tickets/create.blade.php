@extends('layouts.app', ['title' => 'Open Trouble Ticket', 'header' => 'Log Incident Trouble Ticket (TT)'])

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-extrabold text-white">Log Network Incident</h2>
            <p class="text-xs text-slate-400 mt-1">Escalate optical attenuation, packet loss, fiber cut, or customer-end outages to NOC.</p>
        </div>
        <a href="{{ route('tickets.index') }}" class="px-4 py-2 bg-slate-900 border border-slate-800 text-slate-300 hover:text-white rounded-xl text-xs font-semibold">
            &larr; Back to Tickets
        </a>
    </div>

    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-6 bg-slate-900/80 border border-slate-800 rounded-3xl p-8 shadow-2xl">
        @csrf

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Affected Circuit *</label>
                <select name="link_id" id="link_id_select" required class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500 font-mono" onchange="handleCircuitChange(this)">
                    <option value="">-- Select Active Circuit --</option>
                    @foreach($links as $l)
                        <option value="{{ $l->id }}" 
                            data-nttn-provider="{{ $l->nttn_provider ?? 'Summit Communications' }}"
                            data-nttn-link-id="{{ $l->nttn_link_id ?? '' }}"
                            data-customer="{{ $l->customer->name ?? '' }}"
                            data-bandwidth="{{ $l->bandwidth_mbps }}"
                            {{ (old('link_id', $selectedLink?->id) == $l->id) ? 'selected' : '' }}>
                            {{ $l->link_id }} — {{ $l->customer->name }} ({{ $l->circuit_id ?? 'No Circuit ID' }}{{ (!auth()->user()->isReseller() && $l->nttn_link_id) ? ' | NTTN: ' . $l->nttn_link_id : '' }}) [{{ $l->bandwidth_mbps }} Mbps]
                        </option>
                    @endforeach
                </select>
            </div>

            @if(auth()->user()->isReseller())
                <!-- Reseller: Carrier details are hidden, auto-populated seamlessly behind the scenes -->
                <input type="hidden" name="nttn_provider" id="nttn_provider" value="{{ old('nttn_provider', $selectedLink?->nttn_provider ?? 'Summit Communications') }}">
                <input type="hidden" name="nttn_link_id" id="nttn_link_id" value="{{ old('nttn_link_id', $selectedLink?->nttn_link_id ?? '') }}">
                <input type="hidden" name="nttn_provider_email" id="nttn_provider_email" value="{{ old('nttn_provider_email', \App\Http\Controllers\TicketController::getDefaultNttnEmail($selectedLink?->nttn_provider ?? 'Summit Communications')) }}">
                <input type="hidden" name="send_nttn_email" value="1">
            @else
                <!-- NTTN Carrier Escalation & Auto Email Dispatch Card (Internal Staff / Admin) -->
                <div class="p-5 rounded-2xl bg-slate-950/90 border border-blue-500/30 space-y-4 shadow-inner">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-lg bg-blue-500/20 text-blue-400 font-bold flex items-center justify-center text-sm">📡</span>
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-white">NTTN Carrier Escalation &amp; NOC Notification</h3>
                                <p class="text-[11px] text-slate-400">Escalation mail with NTTN Link ID will be auto-dispatched to the selected carrier NOC mail address.</p>
                            </div>
                        </div>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20 font-semibold">
                            Auto Email
                        </span>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">NTTN Provider Company *</label>
                            <select name="nttn_provider" id="nttn_provider" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500" onchange="handleNttnProviderChange(this.value)">
                                @php
                                    $nttnDirectory = \App\Models\SystemSetting::getNttnProviderEmails();
                                    $currentProvider = old('nttn_provider', $selectedLink?->nttn_provider ?? array_key_first($nttnDirectory));
                                @endphp
                                @foreach($nttnDirectory as $provName => $provMail)
                                    <option value="{{ $provName }}" {{ $currentProvider === $provName ? 'selected' : '' }}>{{ $provName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                                NTTN Company Link ID *
                                <span class="text-[10px] lowercase font-normal text-amber-400">(included in carrier email)</span>
                            </label>
                            <input type="text" name="nttn_link_id" id="nttn_link_id" 
                                value="{{ old('nttn_link_id', $selectedLink?->nttn_link_id ?? '') }}" 
                                placeholder="e.g. AIKDBW0032P/S (Carrier NTTN Link ID)"
                                class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span>NTTN Provider Company Mail Address *</span>
                            <span class="text-[10px] lowercase font-normal text-slate-400">Multiple emails comma separated</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <input type="text" name="nttn_provider_email" id="nttn_provider_email"
                                value="{{ old('nttn_provider_email', \App\Http\Controllers\TicketController::getDefaultNttnEmail($selectedLink?->nttn_provider ?? 'Summit Communications')) }}"
                                placeholder="noc@summitcommunications.net, support@summitcommunications.net"
                                class="w-full pl-10 pr-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1 flex items-center gap-1.5">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            Default NOC mail address is automatically populated. You can customize or add multiple addresses.
                        </p>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input type="checkbox" name="send_nttn_email" value="1" checked class="w-4 h-4 rounded text-blue-600 bg-slate-900 border-slate-700 focus:ring-0 focus:ring-offset-0">
                            <span class="text-xs font-semibold text-slate-200">
                                Auto-dispatch fault escalation email to selected NTTN Provider NOC immediately on submit
                            </span>
                        </label>
                    </div>
                </div>
            @endif

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Issue Classification *</label>
                    <select name="type" required class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="Total Optical Loss (Fiber Cut)">Total Optical Loss (Fiber Cut)</option>
                        <option value="High Optical Attenuation (High Loss)">High Optical Attenuation (High Loss)</option>
                        <option value="Packet Drop & Jitter">Packet Drop & Jitter</option>
                        <option value="VLAN Routing Issue">VLAN Routing Issue</option>
                        <option value="Interface Flapping">Interface Flapping</option>
                        <option value="Scheduled Maintenance Inquiry">Scheduled Maintenance Inquiry</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Incident Severity / Priority *</label>
                    <select name="priority" required class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="CRITICAL">CRITICAL (Total Link Down / Red Alert)</option>
                        <option value="HIGH">HIGH (Degraded Service &gt; 50% packet drop)</option>
                        <option value="NORMAL" selected>NORMAL (Intermittent / Latency spike)</option>
                        <option value="LOW">LOW (Informational / Configuration check)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Detailed Incident Description & Ping / Trace Findings *</label>
                <textarea name="description" rows="4" required placeholder="Describe time of occurrence, optical power readings on ODF/SFP, customer terminal observations..."
                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Attach Photo / Screenshot (Optional)</label>
                <div class="flex items-center gap-3">
                    <label class="flex-1 flex items-center gap-3 px-4 py-2.5 bg-slate-950 border border-slate-700 hover:border-slate-600 rounded-xl cursor-pointer text-slate-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span id="file-chosen-text" class="text-xs truncate">Choose picture (e.g. Fiber cut, OTDR trace, ping screenshot)...</span>
                        <input type="file" name="attachment" id="ticket-attachment" accept="image/*" class="hidden" onchange="document.getElementById('file-chosen-text').innerText = this.files[0]?.name || 'Choose picture...';">
                    </label>
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Supported formats: JPG, PNG, WEBP (Max 10MB)</p>
            </div>
        </div>

        <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
            <a href="{{ route('tickets.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-sm font-bold shadow-lg shadow-rose-600/30 transition-all">
                Dispatch Trouble Ticket &rarr;
            </button>
        </div>
    </form>

</div>

<script>
    const nttnEmailDirectory = @json(\App\Models\SystemSetting::getNttnProviderEmails());

    function handleNttnProviderChange(provider) {
        const emailInput = document.getElementById('nttn_provider_email');
        if (nttnEmailDirectory[provider] !== undefined && nttnEmailDirectory[provider] !== '') {
            emailInput.value = nttnEmailDirectory[provider];
        }
    }

    function handleCircuitChange(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        if (!selectedOption || !selectedOption.value) return;

        const provider = selectedOption.getAttribute('data-nttn-provider');
        const linkId = selectedOption.getAttribute('data-nttn-link-id');

        if (provider) {
            const providerSelect = document.getElementById('nttn_provider');
            if (providerSelect) {
                if (providerSelect.tagName === 'SELECT') {
                    // If option exists in dropdown, select it
                    let matched = false;
                    for (let i = 0; i < providerSelect.options.length; i++) {
                        if (providerSelect.options[i].value === provider) {
                            providerSelect.selectedIndex = i;
                            matched = true;
                            break;
                        }
                    }
                    if (!matched) {
                        providerSelect.value = 'Other NTTN';
                    }
                } else {
                    providerSelect.value = provider;
                }
                handleNttnProviderChange(provider);
            }
        }

        if (linkId) {
            const linkIdInput = document.getElementById('nttn_link_id');
            if (linkIdInput) {
                linkIdInput.value = linkId;
            }
        }
    }

    // Auto-trigger on page load if circuit is pre-selected
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('link_id_select');
        if (select && select.value) {
            handleCircuitChange(select);
        }
    });
</script>
@endsection
