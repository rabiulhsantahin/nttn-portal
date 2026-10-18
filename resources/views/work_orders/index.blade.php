@extends('layouts.app', ['title' => 'Work Orders', 'header' => 'Transmission Field Work Orders'])

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Physical Transmission Deployment Board</h2>
            <p class="text-xs text-slate-400 mt-1">Fiber duct laying, splicing at joint boxes, patch panel termination, and NOC handovers.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('work-orders.index', ['status' => 'ISSUED']) }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold {{ request('status') === 'ISSUED' ? 'bg-cyan-600 text-white' : 'bg-slate-900 text-slate-400 border border-slate-800' }}">Issued</a>
            <a href="{{ route('work-orders.index', ['status' => 'WORK_IN_PROGRESS']) }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold {{ request('status') === 'WORK_IN_PROGRESS' ? 'bg-cyan-600 text-white' : 'bg-slate-900 text-slate-400 border border-slate-800' }}">In Progress</a>
            <a href="{{ route('work-orders.index', ['status' => 'COMPLETED']) }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold {{ request('status') === 'COMPLETED' ? 'bg-cyan-600 text-white' : 'bg-slate-900 text-slate-400 border border-slate-800' }}">Completed</a>
            <a href="{{ route('work-orders.index') }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold {{ !request('status') ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-400 border border-slate-800' }}">All</a>
        </div>
    </div>

    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Work Order #</th>
                        <th class="px-6 py-4">Survey Ref</th>
                        <th class="px-6 py-4">Customer & Location</th>
                        <th class="px-6 py-4">Assigned Engineer</th>
                        <th class="px-6 py-4">SLA Deadline</th>
                        <th class="px-6 py-4">Order Status</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($workOrders as $wo)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-cyan-400">
                                {{ $wo->work_order_no }}
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('surveys.show', $wo->surveyRequest) }}" class="font-mono text-blue-400 hover:underline">
                                    {{ $wo->surveyRequest->request_no }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-white font-bold">{{ $wo->surveyRequest->customer->name }}</div>
                                <div class="text-xs text-slate-400">{{ $wo->surveyRequest->customer->district ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-200">{{ $wo->assignedEngineer->name ?? 'Transmission Team' }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs {{ $wo->deadline && $wo->deadline->isPast() ? 'text-rose-400 font-bold' : 'text-slate-300' }}">
                                {{ $wo->deadline?->format('M d, Y') ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold
                                    @if($wo->status === 'COMPLETED') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                    @elseif($wo->status === 'WORK_IN_PROGRESS') bg-cyan-500/10 text-cyan-400 border border-cyan-500/20
                                    @else bg-blue-500/10 text-blue-400 border border-blue-500/20 @endif">
                                    {{ $wo->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('surveys.show', $wo->surveyRequest) }}" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white transition-colors inline-block">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-500">
                                No work orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
