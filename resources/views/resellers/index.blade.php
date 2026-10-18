@extends('layouts.app', ['title' => 'Reseller Directory', 'header' => 'Reseller & ISP Directory'])

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-slate-400">All registered ISP resellers and their circuit portfolio</p>
        </div>
        <a href="{{ route('export.resellers.csv') }}" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-sm font-semibold text-slate-200 transition-colors">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </a>
    </div>

    <!-- Reseller Grid -->
    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($resellers as $reseller)
            <a href="{{ route('resellers.show', $reseller) }}"
               class="block bg-slate-900/80 border border-slate-800 hover:border-blue-500/40 rounded-2xl p-6 shadow-lg transition-all hover:shadow-blue-500/5 group">
                
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-blue-500/20">
                            {{ strtoupper(substr($reseller->company_name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-bold text-white text-sm group-hover:text-blue-300 transition-colors">{{ $reseller->company_name }}</div>
                            <div class="text-xs font-mono text-blue-400">{{ $reseller->reseller_code }}</div>
                        </div>
                    </div>
                    <span class="text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded-full font-mono font-bold">Active</span>
                </div>

                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="text-center p-2.5 rounded-xl bg-slate-950/60 border border-slate-800">
                        <div class="text-xl font-extrabold text-white font-mono">{{ $reseller->customers_count }}</div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Customers</div>
                    </div>
                    <div class="text-center p-2.5 rounded-xl bg-slate-950/60 border border-slate-800">
                        <div class="text-xl font-extrabold text-emerald-400 font-mono">{{ $reseller->links_count }}</div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Circuits</div>
                    </div>
                    <div class="text-center p-2.5 rounded-xl bg-slate-950/60 border border-slate-800">
                        <div class="text-xl font-extrabold text-blue-400 font-mono">{{ $reseller->survey_requests_count }}</div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Surveys</div>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-400">
                    <span>{{ $reseller->phone ?? 'No phone' }}</span>
                    <span class="text-blue-400 font-semibold group-hover:text-blue-300">View Profile →</span>
                </div>
            </a>
        @empty
            <div class="col-span-3 text-center py-16 text-slate-500">
                No resellers found in the system.
            </div>
        @endforelse
    </div>

    @if($resellers->hasPages())
        <div class="mt-4">{{ $resellers->links() }}</div>
    @endif
</div>
@endsection
