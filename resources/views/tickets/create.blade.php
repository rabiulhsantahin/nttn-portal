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

    <form method="POST" action="{{ route('tickets.store') }}" class="space-y-6 bg-slate-900/80 border border-slate-800 rounded-3xl p-8 shadow-2xl">
        @csrf

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Affected Circuit *</label>
                <select name="link_id" required class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500 font-mono">
                    <option value="">-- Select Active Circuit --</option>
                    @foreach($links as $l)
                        <option value="{{ $l->id }}" {{ (old('link_id', $selectedLink?->id) == $l->id) ? 'selected' : '' }}>
                            {{ $l->link_id }} — {{ $l->customer->name }} ({{ $l->circuit_id ?? 'No Circuit ID' }}) [{{ $l->bandwidth_mbps }} Mbps]
                        </option>
                    @endforeach
                </select>
            </div>

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
@endsection
