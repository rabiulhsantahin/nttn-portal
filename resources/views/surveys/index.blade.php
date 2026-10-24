@extends('layouts.app', ['title' => 'Surveys & Orders', 'header' => 'Survey Requests & Work Order Pipeline'])

@section('content')
<div class="space-y-6">

    <!-- Header bar with search and actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('surveys.index') }}" class="flex items-center gap-3 flex-1 max-w-xl">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by request #, customer name, district..."
                class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
            
            <select name="status" onchange="this.form.submit()" class="px-3 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-slate-300 focus:outline-none focus:border-blue-500">
                <option value="">All Statuses</option>
                <option value="SURVEY_REQUESTED" {{ request('status') === 'SURVEY_REQUESTED' ? 'selected' : '' }}>Survey Requested</option>
                <option value="SURVEY_PROCESSING" {{ request('status') === 'SURVEY_PROCESSING' ? 'selected' : '' }}>Survey Processing</option>
                <option value="FEEDBACK_READY" {{ request('status') === 'FEEDBACK_READY' ? 'selected' : '' }}>Feedback Ready</option>
                <option value="MD_APPROVAL_PENDING" {{ request('status') === 'MD_APPROVAL_PENDING' ? 'selected' : '' }}>MD Approval Pending</option>
                <option value="WORK_ORDER_ISSUED" {{ request('status') === 'WORK_ORDER_ISSUED' ? 'selected' : '' }}>Work Order Issued</option>
                <option value="WORK_IN_PROGRESS" {{ request('status') === 'WORK_IN_PROGRESS' ? 'selected' : '' }}>Work In Progress</option>
                <option value="NOC_HANDOVER" {{ request('status') === 'NOC_HANDOVER' ? 'selected' : '' }}>NOC Handover</option>
                <option value="NOC_TESTING" {{ request('status') === 'NOC_TESTING' ? 'selected' : '' }}>NOC Testing</option>
                <option value="BILLING_PENDING" {{ request('status') === 'BILLING_PENDING' ? 'selected' : '' }}>Billing Pending</option>
                <option value="FINAL_MD_APPROVAL" {{ request('status') === 'FINAL_MD_APPROVAL' ? 'selected' : '' }}>Final MD Approval</option>
                <option value="NOT_FEASIBLE" {{ request('status') === 'NOT_FEASIBLE' ? 'selected' : '' }}>Not Feasible</option>
                <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
            </select>

            <button type="submit" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-sm font-semibold transition-colors">
                Filter
            </button>
        </form>

        @if(auth()->user()->isReseller() || auth()->user()->isSuperAdmin())
            <a href="{{ route('surveys.create') }}" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold text-sm shadow-lg shadow-blue-500/20 flex items-center gap-2 self-start sm:self-auto transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>New Survey Request</span>
            </a>
        @endif
    </div>

    <!-- Table Container -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Request #</th>
                        <th class="px-6 py-4">Reseller</th>
                        <th class="px-6 py-4">Customer Name & Location</th>
                        <th class="px-6 py-4">Service & Bandwidth</th>
                        <th class="px-6 py-4">Workflow Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($surveys as $survey)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4">
                                <a href="{{ route('surveys.show', $survey) }}" class="font-mono text-blue-400 hover:underline font-bold text-sm">
                                    {{ $survey->request_no }}
                                </a>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $survey->created_at->format('M d, Y') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-200 font-semibold">{{ $survey->reseller->company_name }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $survey->reseller->reseller_code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-100 font-bold">{{ $survey->customer->name }}</div>
                                <div class="text-xs text-slate-400">{{ $survey->customer->thana ?? '' }}, {{ $survey->customer->district ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-cyan-400 font-bold font-mono text-sm">{{ $survey->bandwidth_mbps }} Mbps</div>
                                <div class="text-xs text-slate-400">{{ $survey->service_type }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-mono font-bold
                                    @if($survey->status === 'ACTIVE') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                    @elseif(str_contains($survey->status, 'MD_APPROVAL')) bg-amber-500/10 text-amber-400 border border-amber-500/20
                                    @elseif(str_contains($survey->status, 'REJECTED')) bg-rose-500/10 text-rose-400 border border-rose-500/20
                                    @elseif($survey->status === 'NOT_FEASIBLE') bg-rose-500/10 text-rose-400 border border-rose-500/20
                                    @elseif($survey->status === 'CANCELLED') bg-slate-500/10 text-slate-400 border border-slate-500/20
                                    @elseif(str_contains($survey->status, 'WORK')) bg-cyan-500/10 text-cyan-400 border border-cyan-500/20
                                    @elseif(str_contains($survey->status, 'NOC')) bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                    @elseif(str_contains($survey->status, 'BILLING')) bg-violet-500/10 text-violet-400 border border-violet-500/20
                                    @else bg-blue-500/10 text-blue-400 border border-blue-500/20 @endif">
                                    {{ $survey->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('surveys.show', $survey) }}" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white transition-colors inline-block">
                                        View Details &rarr;
                                    </a>
                                    @if(auth()->user()->isSuperAdmin())
                                        <form method="POST" action="{{ route('surveys.destroy', $survey) }}" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete survey {{ $survey->request_no }}? All associated feasibility records, work orders, and logs will be removed.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Delete Survey" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No survey requests found matching the current criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($surveys->hasPages())
            <div class="px-6 py-4 border-t border-slate-800">
                {{ $surveys->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
