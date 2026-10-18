@extends('layouts.app', ['title' => 'Customer Directory', 'header' => 'Customer Directory'])

@section('content')
<div class="space-y-6">

    <!-- Search bar -->
    <div class="flex items-center gap-3">
        <form method="GET" action="{{ route('customers.index') }}" class="flex items-center gap-3 flex-1 max-w-xl">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search by name, district, thana, phone..."
                   class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
            <button type="submit" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-semibold transition-colors">
                Search
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[11px] uppercase tracking-wider font-semibold text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Location</th>
                        <th class="px-6 py-4">Reseller</th>
                        <th class="px-6 py-4">Circuits</th>
                        <th class="px-6 py-4">Surveys</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center justify-center font-bold text-sm flex-shrink-0">
                                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-100">{{ $customer->name }}</div>
                                        <div class="text-xs text-slate-400 font-mono">{{ $customer->phone ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-200">{{ $customer->thana ?? '—' }}</div>
                                <div class="text-xs text-slate-400">{{ $customer->district ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-300 text-sm">{{ $customer->reseller->company_name ?? 'Direct' }}</td>
                            <td class="px-6 py-4">
                                <span class="font-mono font-bold text-emerald-400">{{ $customer->links_count }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono font-bold text-blue-400">{{ $customer->survey_requests_count }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('customers.show', $customer) }}"
                                   class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white transition-colors inline-block">
                                    View Profile →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="px-6 py-4 border-t border-slate-800">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
