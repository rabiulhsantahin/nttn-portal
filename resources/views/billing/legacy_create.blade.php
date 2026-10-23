@extends('layouts.app', ['title' => 'Manual Legacy Data Entry', 'header' => 'Legacy Circuit & Billing Ingestion Desk'])

@section('content')
<div class="max-w-6xl mx-auto space-y-8">

    <!-- Top Navigation / Back link -->
    <div class="flex items-center justify-between">
        <a href="{{ route('billing.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Billing & Invoicing</span>
        </a>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                MD Approval Required
            </span>
        </div>
    </div>

    <!-- Header Description Card -->
    <div class="bg-gradient-to-r from-violet-950/40 via-slate-900 to-slate-900 border border-violet-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute -right-8 -bottom-8 w-36 h-36 bg-violet-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
            <div class="space-y-2">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-violet-500/20 text-violet-300 flex items-center justify-center font-bold text-base">📋</span>
                    <h2 class="text-2xl font-black text-white tracking-tight">Manual Legacy Data Entry Desk (আগের সার্কিট ও বিলিং ডাটা এন্ট্রি)</h2>
                </div>
                <p class="text-xs text-slate-300 max-w-2xl leading-relaxed">
                    সিস্টেম চালুর পূর্ববর্তী লাইভ সার্কিট ও বকেয়া/ইতিহাসগত বিলিং রেকর্ড এমডি স্যারের অনুমোদন সাপেক্ষে ডাটাবেজে ম্যানুয়ালি যুক্ত করুন। অনুমোদিত হলে স্বয়ংক্রিয়ভাবে সংশ্লিষ্ট রিসেলারের পোর্টালে সার্কিট ও বিল প্রদর্শিত হবে।
                </p>
            </div>
            <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 text-xs font-mono space-y-1 text-slate-300">
                <div class="text-[11px] text-slate-400 uppercase font-bold tracking-wider">Workflow Governance</div>
                <div class="text-emerald-400">✓ Strict Reseller Multi-Tenancy</div>
                <div class="text-amber-400">✓ MD Executive Sign-Off</div>
                <div class="text-cyan-400">✓ Auto-Provisioned Live Chat & TT</div>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
            <div class="font-bold text-sm">Please correct the following errors:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('billing.legacy.store') }}" enctype="multipart/form-data" class="space-y-8">
        @csrf

        <!-- Section 1: Reseller & Customer Assignment -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-blue-500/10 text-blue-400 font-bold flex items-center justify-center text-xs">1</span>
                    <div>
                        <h3 class="text-base font-bold text-white">Reseller & Customer Assignment (রিসেলার ও গ্রাহক নির্ধারণ)</h3>
                        <p class="text-xs text-slate-400">ডাটা কোন রিসেলারের পোর্টালে যাবে তা নির্বাচন করুন</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Reseller Selection -->
                <div class="space-y-2">
                    <label for="reseller_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                        Target Reseller (রিসেলার নির্বাচন) <span class="text-rose-400">*</span>
                    </label>
                    <select name="reseller_id" id="reseller_id" required onchange="handleResellerChange(this)"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-violet-500">
                        <option value="">-- Select Reseller --</option>
                        @foreach($resellers as $reseller)
                            <option value="{{ $reseller->id }}" {{ old('reseller_id') == $reseller->id ? 'selected' : '' }}
                                data-customers='@json($reseller->customers)'>
                                {{ $reseller->company_name }} ({{ $reseller->reseller_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Customer Mode Choice -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                        Customer Assignment Mode <span class="text-rose-400">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-950 border border-slate-700 cursor-pointer hover:border-slate-600 transition-colors">
                            <input type="radio" name="customer_mode" value="new" {{ old('customer_mode', 'new') === 'new' ? 'checked' : '' }} onchange="toggleCustomerMode('new')" class="text-violet-500 focus:ring-0">
                            <span class="text-xs font-medium text-white">New End-Customer</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-950 border border-slate-700 cursor-pointer hover:border-slate-600 transition-colors">
                            <input type="radio" name="customer_mode" value="existing" {{ old('customer_mode') === 'existing' ? 'checked' : '' }} onchange="toggleCustomerMode('existing')" class="text-violet-500 focus:ring-0">
                            <span class="text-xs font-medium text-white">Existing Customer</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Existing Customer Select Box -->
            <div id="existing_customer_wrap" class="space-y-2 {{ old('customer_mode') === 'existing' ? '' : 'hidden' }}">
                <label for="customer_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                    Select Existing Registered Customer
                </label>
                <select name="customer_id" id="customer_id"
                    class="w-full px-4 py-3 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-violet-500">
                    <option value="">-- Choose Customer --</option>
                </select>
            </div>

            <!-- New Customer Fields -->
            <div id="new_customer_fields" class="space-y-4 pt-2 {{ old('customer_mode', 'new') === 'new' ? '' : 'hidden' }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="customer_name" class="block text-xs font-semibold uppercase text-slate-300">
                            Customer / Organization Name <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" name="customer_name" id="customer_name" value="{{ old('customer_name') }}"
                            placeholder="e.g. Grameenphone Banani POP or Bank Asia Head Office"
                            class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-violet-500">
                    </div>

                    <div class="space-y-1.5">
                        <label for="customer_phone" class="block text-xs font-semibold uppercase text-slate-300">
                            Contact Phone
                        </label>
                        <input type="text" name="customer_phone" id="customer_phone" value="{{ old('customer_phone') }}"
                            placeholder="+8801700000000"
                            class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-violet-500">
                    </div>

                    <div class="space-y-1.5">
                        <label for="customer_district" class="block text-xs font-semibold uppercase text-slate-300">
                            District / Division
                        </label>
                        <input type="text" name="customer_district" id="customer_district" value="{{ old('customer_district', 'Dhaka') }}"
                            placeholder="e.g. Dhaka, Chittagong, Sylhet"
                            class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-violet-500">
                    </div>

                    <div class="space-y-1.5">
                        <label for="customer_thana" class="block text-xs font-semibold uppercase text-slate-300">
                            Thana / Area
                        </label>
                        <input type="text" name="customer_thana" id="customer_thana" value="{{ old('customer_thana') }}"
                            placeholder="e.g. Gulshan, Motijheel, Agrabad"
                            class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-violet-500">
                    </div>

                    <div class="space-y-1.5 sm:col-span-3">
                        <label for="customer_address" class="block text-xs font-semibold uppercase text-slate-300">
                            Detailed Premises Address
                        </label>
                        <input type="text" name="customer_address" id="customer_address" value="{{ old('customer_address') }}"
                            placeholder="Plot, Road, Building Floor details..."
                            class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-violet-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Technical & Carrier Specifications -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-cyan-500/10 text-cyan-400 font-bold flex items-center justify-center text-xs">2</span>
                    <div>
                        <h3 class="text-base font-bold text-white">Circuit & Carrier Specifications (সার্কিট ও টেকনিক্যাল তথ্য)</h3>
                        <p class="text-xs text-slate-400">ট্রান্সমিশন ও অপটিক্যাল নেটওয়ার্ক স্পেসিফিকেশন</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="space-y-1.5">
                    <label for="circuit_id" class="block text-xs font-semibold uppercase text-slate-300">
                        Circuit ID (সার্কিট নম্বর)
                    </label>
                    <input type="text" name="circuit_id" id="circuit_id" value="{{ old('circuit_id') }}"
                        placeholder="e.g. CIR-2023-DHK-0912 (Auto-generates if empty)"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-cyan-500">
                </div>

                <div class="space-y-1.5">
                    <label for="link_id" class="block text-xs font-semibold uppercase text-slate-300">
                        System Link ID (সিস্টেম লিংক আইডি)
                    </label>
                    <input type="text" name="link_id" id="link_id" value="{{ old('link_id') }}"
                        placeholder="e.g. LNK-LEG-XXXX (Auto-generates if empty)"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-cyan-500">
                </div>

                <div class="space-y-1.5">
                    <label for="bandwidth_mbps" class="block text-xs font-semibold uppercase text-slate-300">
                        Bandwidth (Mbps) <span class="text-rose-400">*</span>
                    </label>
                    <input type="number" min="1" step="1" name="bandwidth_mbps" id="bandwidth_mbps" required value="{{ old('bandwidth_mbps', 100) }}"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-cyan-500">
                </div>

                <div class="space-y-1.5">
                    <label for="nttn_provider" class="block text-xs font-semibold uppercase text-slate-300">
                        NTTN Carrier / Provider <span class="text-rose-400">*</span>
                    </label>
                    <select name="nttn_provider" id="nttn_provider" required
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-cyan-500">
                        <option value="Summit Communications" {{ old('nttn_provider') === 'Summit Communications' ? 'selected' : '' }}>Summit Communications</option>
                        <option value="Fiber@Home" {{ old('nttn_provider') === 'Fiber@Home' ? 'selected' : '' }}>Fiber@Home</option>
                        <option value="Bahon Limited" {{ old('nttn_provider') === 'Bahon Limited' ? 'selected' : '' }}>Bahon Limited</option>
                        <option value="Direct Ring Core" {{ old('nttn_provider') === 'Direct Ring Core' ? 'selected' : '' }}>Direct Ring Core</option>
                        <option value="Other NTTN" {{ old('nttn_provider') === 'Other NTTN' ? 'selected' : '' }}>Other NTTN</option>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label for="nttn_link_id" class="block text-xs font-semibold uppercase text-slate-300">
                        Carrier NTTN Circuit / Link ID
                    </label>
                    <input type="text" name="nttn_link_id" id="nttn_link_id" value="{{ old('nttn_link_id') }}"
                        placeholder="e.g. SCL-LINK-99214"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-cyan-500">
                </div>

                <div class="space-y-1.5">
                    <label for="activation_date" class="block text-xs font-semibold uppercase text-slate-300">
                        Original Activation Date (চালুর তারিখ) <span class="text-rose-400">*</span>
                    </label>
                    <input type="date" name="activation_date" id="activation_date" required value="{{ old('activation_date', date('Y-m-d')) }}"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-cyan-500">
                </div>

                <div class="space-y-1.5">
                    <label for="vlan" class="block text-xs font-semibold uppercase text-slate-300">
                        VLAN Identifier
                    </label>
                    <input type="text" name="vlan" id="vlan" value="{{ old('vlan', '2040') }}"
                        placeholder="e.g. 2040"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-cyan-500">
                </div>

                <div class="space-y-1.5 sm:col-span-2">
                    <label for="port" class="block text-xs font-semibold uppercase text-slate-300">
                        POP & Switch Port Details
                    </label>
                    <input type="text" name="port" id="port" value="{{ old('port') }}"
                        placeholder="e.g. TenGigE-0/1/4 @ Mohakhali Aggregation POP"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-cyan-500">
                </div>
            </div>
        </div>

        <!-- Section 3: Commercial Billing & Financial Ledger -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-400 font-bold flex items-center justify-center text-xs">3</span>
                    <div>
                        <h3 class="text-base font-bold text-white">Commercial Rates & Billing (বিলিং ও আর্থিক রেট)</h3>
                        <p class="text-xs text-slate-400">এই রেট অনুযায়ী রিসেলারের মাসিক মোট বিল হিসাব করা হবে</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="space-y-1.5">
                    <label for="monthly_charge" class="block text-xs font-semibold uppercase text-slate-300">
                        Monthly Recurring Charge (মাসিক বিল ৳) <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center font-bold text-slate-500">৳</span>
                        <input type="number" step="0.01" min="0" name="monthly_charge" id="monthly_charge" required value="{{ old('monthly_charge') }}"
                            placeholder="25000.00"
                            class="w-full pl-8 pr-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-emerald-400 font-mono font-bold focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="installation_charge" class="block text-xs font-semibold uppercase text-slate-300">
                        Installation Fee / Capex (এককালীন ফি ৳)
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center font-bold text-slate-500">৳</span>
                        <input type="number" step="0.01" min="0" name="installation_charge" id="installation_charge" value="{{ old('installation_charge', 0) }}"
                            placeholder="10000.00"
                            class="w-full pl-8 pr-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-amber-400 font-mono font-bold focus:outline-none focus:border-amber-500">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="nttn_cost" class="block text-xs font-semibold uppercase text-slate-300">
                        Carrier NTTN Cost (ক্যারিয়ার খরচ ৳)
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center font-bold text-slate-500">৳</span>
                        <input type="number" step="0.01" min="0" name="nttn_cost" id="nttn_cost" value="{{ old('nttn_cost', 0) }}"
                            placeholder="12000.00"
                            class="w-full pl-8 pr-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-slate-300 font-mono focus:outline-none focus:border-slate-500">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="invoice_no" class="block text-xs font-semibold uppercase text-slate-300">
                        Invoice # (চালান নম্বর)
                    </label>
                    <input type="text" name="invoice_no" id="invoice_no" value="{{ old('invoice_no') }}"
                        placeholder="e.g. INV-2023-0891 (Auto-generates if empty)"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-emerald-500">
                </div>

                <div class="space-y-1.5">
                    <label for="billing_start_date" class="block text-xs font-semibold uppercase text-slate-300">
                        Billing Start Date
                    </label>
                    <input type="date" name="billing_start_date" id="billing_start_date" value="{{ old('billing_start_date') }}"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-emerald-500">
                </div>

                <div class="space-y-1.5">
                    <label for="due_date" class="block text-xs font-semibold uppercase text-slate-300">
                        Invoice Due Date
                    </label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-emerald-500">
                </div>
            </div>
        </div>

        <!-- Section 4: Executive MD Approval & Memo Verification -->
        <div class="bg-slate-900/80 border border-amber-500/30 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6 relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex items-center justify-between border-b border-slate-800 pb-4 relative z-10">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-400 font-bold flex items-center justify-center text-xs">4</span>
                    <div>
                        <h3 class="text-base font-bold text-white">Executive MD Approval & Memo Record (এমডি স্যারের অনুমোদন ও নথি)</h3>
                        <p class="text-xs text-slate-400">অনুমোদনের মেমো নম্বর ও স্ক্যান কপি আপলোড করুন</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 relative z-10">
                <div class="space-y-1.5">
                    <label for="md_approval_ref" class="block text-xs font-semibold uppercase text-slate-300">
                        MD Approval Memo / Reference No (মেমো বা রেফারেন্স নম্বর)
                    </label>
                    <input type="text" name="md_approval_ref" id="md_approval_ref" value="{{ old('md_approval_ref') }}"
                        placeholder="e.g. NTTN/MD/OFFICE/2023-MEMO-441"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-amber-300 font-mono focus:outline-none focus:border-amber-500">
                </div>

                <div class="space-y-1.5">
                    <label for="md_approval_doc" class="block text-xs font-semibold uppercase text-slate-300">
                        Scanned Approval Memo / Contract Document (অনুমোদনের কপি)
                    </label>
                    <input type="file" name="md_approval_doc" id="md_approval_doc" accept=".pdf,.png,.jpg,.jpeg"
                        class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-600 file:text-white hover:file:bg-amber-500">
                    <p class="text-[10px] text-slate-400">PDF, JPG, PNG up to 10MB.</p>
                </div>

                <div class="space-y-1.5 sm:col-span-2">
                    <label for="legacy_notes" class="block text-xs font-semibold uppercase text-slate-300">
                        Legacy Remarks & Historical Context (বিবরণ বা নোট)
                    </label>
                    <textarea name="legacy_notes" id="legacy_notes" rows="3"
                        placeholder="Mention any historical SLA, discount agreement, or contract specifics agreed upon with MD Sir..."
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-amber-500">{{ old('legacy_notes') }}</textarea>
                </div>
            </div>

            @if(auth()->user()->isMd() || auth()->user()->isSuperAdmin())
                <div class="pt-4 border-t border-slate-800/80 relative z-10">
                    <label class="flex items-center gap-3 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 cursor-pointer">
                        <input type="checkbox" name="direct_approve" value="1" {{ old('direct_approve', '1') ? 'checked' : '' }}
                            class="w-4 h-4 rounded text-amber-500 focus:ring-0 bg-slate-950 border-slate-700">
                        <div class="text-xs">
                            <span class="font-bold text-amber-300">Directly Approve & Activate Immediately (তাৎক্ষণিক অনুমোদন ও সক্রিয়করণ)</span>
                            <p class="text-slate-400 text-[11px]">Since you are logged in as MD / Super Admin, this circuit will be marked ACTIVE and immediately visible in the reseller's portal.</p>
                        </div>
                    </label>
                </div>
            @else
                <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 text-xs text-slate-400 flex items-center gap-2.5 relative z-10">
                    <span class="text-base text-amber-400">ℹ️</span>
                    <span><strong>Submission Notice:</strong> As a Billing user, submitting this form will queue this legacy circuit for executive sign-off on <strong>MD Sir's Approvals Desk</strong>. Once MD Sir approves, it goes live in the reseller's portal.</span>
                </div>
            @endif
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end gap-4 pt-4">
            <a href="{{ route('billing.index') }}" class="px-6 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-gradient-to-r from-violet-600 via-indigo-600 to-blue-600 hover:from-violet-500 hover:to-blue-500 text-white font-bold text-sm shadow-xl shadow-indigo-600/30 flex items-center gap-2 transition-all">
                <span>Save & Submit Legacy Record &rarr;</span>
            </button>
        </div>
    </form>
</div>

<script>
    function handleResellerChange(selectEl) {
        const option = selectEl.options[selectEl.selectedIndex];
        const customerSelect = document.getElementById('customer_id');
        customerSelect.innerHTML = '<option value="">-- Choose Customer --</option>';

        if (!option || !option.dataset.customers) return;

        try {
            const customers = JSON.parse(option.dataset.customers);
            customers.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = `${c.name} (${c.district || 'Dhaka'})`;
                customerSelect.appendChild(opt);
            });
        } catch (e) {
            console.error('Failed to parse customers data', e);
        }
    }

    function toggleCustomerMode(mode) {
        const existingWrap = document.getElementById('existing_customer_wrap');
        const newFields = document.getElementById('new_customer_fields');
        const customerName = document.getElementById('customer_name');
        const customerId = document.getElementById('customer_id');

        if (mode === 'existing') {
            existingWrap.classList.remove('hidden');
            newFields.classList.add('hidden');
            customerName.required = false;
            customerId.required = true;
        } else {
            existingWrap.classList.add('hidden');
            newFields.classList.remove('hidden');
            customerName.required = true;
            customerId.required = false;
        }
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function () {
        const resellerSelect = document.getElementById('reseller_id');
        if (resellerSelect && resellerSelect.value) {
            handleResellerChange(resellerSelect);
            @if(old('customer_id'))
                document.getElementById('customer_id').value = "{{ old('customer_id') }}";
            @endif
        }
    });
</script>
@endsection
