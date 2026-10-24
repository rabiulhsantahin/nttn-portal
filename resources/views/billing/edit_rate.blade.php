@extends('layouts.app', ['title' => 'Edit Circuit Rate', 'header' => 'Commercial Rate Revision Desk'])

@section('content')
<div class="max-w-4xl mx-auto space-y-8">

    <!-- Top Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('billing.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Billing Overview</span>
        </a>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                MD Approval Required
            </span>
        </div>
    </div>

    <!-- Header Circuit Card -->
    <div class="bg-gradient-to-r from-violet-950/40 via-slate-900 to-slate-900 border border-violet-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute -right-8 -bottom-8 w-36 h-36 bg-violet-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-violet-500/20 text-violet-300 flex items-center justify-center font-bold text-base">💰</span>
                    <h2 class="text-2xl font-black text-white tracking-tight">Circuit Commercial Rate Revision (রেট পরিবর্তন)</h2>
                </div>
                <p class="text-xs text-slate-300 mt-1">
                    সার্কিট: <strong class="text-cyan-400 font-mono">{{ $link->circuit_id ?? $link->link_id }}</strong> | গ্রাহক: <strong class="text-white">{{ $link->customer->name }}</strong> | রিসেলার: <strong class="text-violet-300">{{ $link->reseller->company_name }}</strong>
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-4 text-xs font-mono text-slate-300">
                    <span>ব্যান্ডউইথ: <strong class="text-blue-400">{{ $link->bandwidth_mbps }} Mbps</strong></span>
                    <span>ক্যারিয়ার: <strong class="text-slate-200">{{ $link->nttn_provider }}</strong></span>
                    <span>বর্তমান মাসিক বিল: <strong class="text-emerald-400 text-sm">৳ {{ number_format($link->billing?->monthly_charge ?? 0, 2) }}</strong></span>
                </div>
            </div>

            @if($link->pendingRateRevision)
                <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-xs font-mono space-y-1">
                    <div class="text-[11px] text-amber-400 font-bold uppercase">Pending Revision in Progress</div>
                    <div class="text-slate-300">Proposed: <strong class="text-amber-300 font-bold">৳ {{ number_format($link->pendingRateRevision->new_monthly_charge, 2) }}</strong></div>
                    <div class="text-[10px] text-slate-400">Awaiting MD Sign-Off</div>
                </div>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
            <div class="font-bold text-sm">Please correct the following:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('billing.links.revise-rate', $link) }}" class="space-y-6">
        @csrf

        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
            <div class="border-b border-slate-800 pb-4">
                <h3 class="text-base font-bold text-white">Adjust Billing Figures (নতুন রেট ও বাণিজ্যিক তথ্য)</h3>
                <p class="text-xs text-slate-400">এমডি স্যারের অনুমোদনের পরেই কেবল এই নতুন বিল রিসেলারের পোর্টালে কার্যকরী হবে</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Current Monthly Rate (Read-only reference) -->
                <div class="space-y-1.5 p-4 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <span class="text-xs text-slate-400 uppercase font-semibold">Current Active Monthly Bill (বর্তমান বিল)</span>
                    <div class="text-2xl font-black text-slate-300 font-mono">
                        ৳ {{ number_format($link->billing?->monthly_charge ?? 0, 2) }}
                    </div>
                    <p class="text-[11px] text-cyan-400 font-mono font-semibold">Unit Price: ৳ {{ number_format($link->unit_price, 2) }} / Mbps ({{ $link->bandwidth_mbps }} Mbps)</p>
                    <p class="text-[11px] text-slate-500">Reseller portal currently sees this rate.</p>
                </div>

                <!-- New Proposed Monthly Rate -->
                <div class="space-y-1.5 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30">
                    <div class="flex items-center justify-between">
                        <label for="monthly_charge" class="block text-xs text-emerald-400 uppercase font-bold">
                            New Monthly Bill (নতুন মাসিক বিল ৳) <span class="text-rose-400">*</span>
                        </label>
                        <span id="new_unit_price_calc" class="text-xs text-cyan-400 font-mono font-bold"></span>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center font-bold text-emerald-400">৳</span>
                        <input type="number" step="0.01" min="0" name="monthly_charge" id="monthly_charge" required
                            value="{{ old('monthly_charge', $link->billing?->monthly_charge) }}"
                            class="w-full pl-8 pr-4 py-2 bg-slate-950 border border-emerald-500/50 rounded-xl text-lg font-mono font-bold text-white focus:outline-none focus:border-emerald-400">
                    </div>
                    <p class="text-[11px] text-emerald-300/80">Will take effect in reseller portal after MD approval.</p>
                </div>

                <!-- Installation Fee -->
                <div class="space-y-1.5">
                    <label for="installation_charge" class="block text-xs font-semibold uppercase text-slate-300">
                        One-Time Installation Fee / Capex (এককালীন ফি ৳)
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center font-bold text-slate-500">৳</span>
                        <input type="number" step="0.01" min="0" name="installation_charge" id="installation_charge"
                            value="{{ old('installation_charge', $link->billing?->installation_charge ?? 0) }}"
                            class="w-full pl-8 pr-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm font-mono text-amber-400 focus:outline-none focus:border-amber-500">
                    </div>
                </div>

                <!-- Carrier NTTN Cost -->
                <div class="space-y-1.5">
                    <label for="nttn_cost" class="block text-xs font-semibold uppercase text-slate-300">
                        Carrier NTTN Cost (ক্যারিয়ার খরচ ৳)
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center font-bold text-slate-500">৳</span>
                        <input type="number" step="0.01" min="0" name="nttn_cost" id="nttn_cost"
                            value="{{ old('nttn_cost', $link->billing?->nttn_cost ?? 0) }}"
                            class="w-full pl-8 pr-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm font-mono text-slate-300 focus:outline-none focus:border-slate-500">
                    </div>
                </div>

                <!-- Reseller Assignment -->
                <div class="space-y-1.5">
                    <label for="reseller_id" class="block text-xs font-semibold uppercase text-slate-300">
                        Reseller Assignment (রেসেলার)
                    </label>
                    <select name="reseller_id" id="reseller_id"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm font-medium text-white focus:outline-none focus:border-violet-500">
                        @foreach($resellers as $reseller)
                            <option value="{{ $reseller->id }}" {{ (string) old('reseller_id', $link->reseller_id) === (string) $reseller->id ? 'selected' : '' }}>
                                {{ $reseller->company_name }} ({{ $reseller->reseller_code }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500">Proposed reseller change takes effect upon MD approval.</p>
                </div>

                <!-- NTTN Carrier / Provider -->
                <div class="space-y-1.5">
                    <label for="nttn_provider" class="block text-xs font-semibold uppercase text-slate-300">
                        NTTN Carrier / Provider (এনটিটিএন কোম্পানি)
                    </label>
                    @php
                        $standardProviders = ['Summit Communications', 'Fiber@Home', 'Bahon Limited', 'Direct Ring Core', 'Other NTTN'];
                        $currentProvider = old('nttn_provider', $link->nttn_provider);
                    @endphp
                    <select name="nttn_provider" id="nttn_provider"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm font-medium text-white focus:outline-none focus:border-cyan-500">
                        @if($currentProvider && !in_array($currentProvider, $standardProviders))
                            <option value="{{ $currentProvider }}" selected>{{ $currentProvider }}</option>
                        @endif
                        @foreach($standardProviders as $provider)
                            <option value="{{ $provider }}" {{ $currentProvider === $provider ? 'selected' : '' }}>
                                {{ $provider }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500">Proposed carrier change takes effect upon MD approval.</p>
                </div>

                <!-- Effective Date -->
                <div class="space-y-1.5 sm:col-span-2">
                    <label for="effective_date" class="block text-xs font-semibold uppercase text-slate-300">
                        Effective Billing Cycle Date (কার্যকরী তারিখ)
                    </label>
                    <input type="date" name="effective_date" id="effective_date"
                        value="{{ old('effective_date', date('Y-m-d')) }}"
                        class="w-full sm:w-1/2 px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm font-mono text-white focus:outline-none focus:border-cyan-500">
                </div>

                <!-- Reason / Justification -->
                <div class="space-y-1.5 sm:col-span-2">
                    <label for="reason" class="block text-xs font-semibold uppercase text-slate-300">
                        Reason / Justification for Rate Revision (রেট পরিবর্তনের কারণ / মেমো রেফারেন্স) <span class="text-rose-400">*</span>
                    </label>
                    <textarea name="reason" id="reason" rows="3" required
                        placeholder="e.g. As agreed with MD Sir, applying corporate bulk discount of ৳ 5,000 for FY2026 under Memo #MD/NTTN/CORP-88..."
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-violet-500">{{ old('reason') }}</textarea>
                </div>
            </div>

            @if(auth()->user()->isMd() || auth()->user()->isSuperAdmin())
                <div class="pt-4 border-t border-slate-800">
                    <label class="flex items-center gap-3 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 cursor-pointer">
                        <input type="checkbox" name="direct_approve" value="1" {{ old('direct_approve', '1') ? 'checked' : '' }}
                            class="w-4 h-4 rounded text-amber-500 focus:ring-0 bg-slate-950 border-slate-700">
                        <div class="text-xs">
                            <span class="font-bold text-amber-300">Directly Approve & Apply Immediately (তাৎক্ষণিক অনুমোদন ও কার্যকর করুন)</span>
                            <p class="text-slate-400 text-[11px]">Since you are logged in as MD / Super Admin, this will update the reseller's rate right away.</p>
                        </div>
                    </label>
                </div>
            @else
                <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 text-xs text-slate-400 flex items-center gap-2.5">
                    <span class="text-base text-amber-400">ℹ️</span>
                    <span><strong>Workflow Governance:</strong> Submitting this will keep the circuit's current bill intact in the reseller portal until <strong>MD Sir reviews and approves</strong> the revision from MD Approvals.</span>
                </div>
            @endif
        </div>

        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('billing.index') }}" class="px-6 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 hover:from-emerald-500 hover:to-cyan-500 text-white font-bold text-sm shadow-xl shadow-emerald-600/30 flex items-center gap-2 transition-all">
                <span>Submit Rate Revision for MD Approval &rarr;</span>
            </button>
        </div>
    </form>

    <!-- Previous Rate Revisions on this circuit -->
    @if($link->rateRevisions->isNotEmpty())
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
            <div class="px-6 py-4 border-b border-slate-800 bg-slate-950/40">
                <h4 class="font-bold text-white text-sm">Previous Rate Revision History on this Circuit</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-slate-400 uppercase bg-slate-950/60 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Old Rate &rarr; New Rate</th>
                            <th class="px-6 py-3">Reason</th>
                            <th class="px-6 py-3">Requested By</th>
                            <th class="px-6 py-3 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono">
                        @foreach($link->rateRevisions as $rev)
                            <tr>
                                <td class="px-6 py-3 text-slate-300">
                                    {{ $rev->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-3">
                                    <span class="text-slate-400">৳ {{ number_format($rev->old_monthly_charge, 2) }}</span>
                                    <span class="text-slate-500">&rarr;</span>
                                    <span class="text-emerald-400 font-bold">৳ {{ number_format($rev->new_monthly_charge, 2) }}</span>
                                </td>
                                <td class="px-6 py-3 font-sans text-slate-300">
                                    {{ $rev->reason }}
                                </td>
                                <td class="px-6 py-3 font-sans text-slate-400">
                                    {{ $rev->requester->name ?? 'Billing' }}
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                                        @if($rev->status === 'APPROVED') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                        @elseif($rev->status === 'PENDING') bg-amber-500/10 text-amber-400 border border-amber-500/20
                                        @else bg-rose-500/10 text-rose-400 border border-rose-500/20 @endif">
                                        {{ $rev->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

<script>
    const linkBw = {{ (int) $link->bandwidth_mbps }};
    function updateNewUnitPrice() {
        const val = parseFloat(document.getElementById('monthly_charge').value) || 0;
        const el = document.getElementById('new_unit_price_calc');
        if (linkBw > 0 && el) {
            const up = (val / linkBw).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            el.textContent = `Unit Price: ৳ ${up} / Mbps`;
        }
    }
    document.getElementById('monthly_charge')?.addEventListener('input', updateNewUnitPrice);
    updateNewUnitPrice();
</script>
@endsection
