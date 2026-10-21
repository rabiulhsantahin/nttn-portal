@extends('layouts.app', ['title' => 'NOC Testing Desk', 'header' => 'Network Operations Center (NOC) Quality & SLA Verification'])

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">NOC Handover & 8-Point Verification Bench</h2>
            <p class="text-xs text-slate-400 mt-1">Optical power verification, VLAN tagging, BER/throughput testing, and NMS telemetry enrollment.</p>
        </div>
    </div>

    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Handover #</th>
                        <th class="px-6 py-4">Survey / Order</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Provisioned Circuit</th>
                        <th class="px-6 py-4">Test Result</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Testing Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($handovers as $h)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-emerald-400">
                                #HO-{{ $h->id }}
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('surveys.show', $h->workOrder->surveyRequest) }}" class="font-mono text-blue-400 hover:underline">
                                    {{ $h->workOrder->surveyRequest->request_no }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-white font-bold">{{ $h->workOrder->surveyRequest->customer->name }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-cyan-400 font-bold">
                                <div>{{ $h->link?->circuit_id ?? 'Pending' }}</div>
                                @if(!auth()->user()->isReseller() && $h->link?->nttn_link_id)
                                    <div class="text-[11px] text-emerald-400 font-normal">NTTN: {{ $h->link->nttn_link_id }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                @if($h->test)
                                    <span class="px-2 py-0.5 rounded-full font-bold {{ $h->test->result === 'PASSED' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                                        {{ $h->test->result }}
                                    </span>
                                @else
                                    <span class="text-slate-500">Not Tested</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold
                                    @if($h->status === 'VERIFIED') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                    @elseif($h->status === 'TESTING') bg-amber-500/10 text-amber-400 border border-amber-500/20
                                    @elseif($h->status === 'REJECTED') bg-rose-500/10 text-rose-400 border border-rose-500/20
                                    @else bg-blue-500/10 text-blue-400 border border-blue-500/20 @endif">
                                    {{ $h->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('surveys.show', $h->workOrder->surveyRequest) }}" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs font-bold text-white transition-colors inline-block">
                                    Run SLA Checklist &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-500">
                                No pending NOC handovers in queue.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
