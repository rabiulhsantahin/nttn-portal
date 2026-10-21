@extends('layouts.app', ['title' => 'Initiate Survey', 'header' => 'New Transmission Survey Request'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-extrabold text-white">Lodge Survey Request</h2>
            <p class="text-xs text-slate-400 mt-1">Submit fiber feasibility parameters for transmission engineering analysis.</p>
        </div>
        <a href="{{ route('surveys.index') }}" class="px-4 py-2 bg-slate-900 border border-slate-800 text-slate-300 hover:text-white rounded-xl text-xs font-semibold">
            &larr; Back to Pipeline
        </a>
    </div>

    <form method="POST" action="{{ route('surveys.store') }}" class="space-y-6 bg-slate-900/80 border border-slate-800 rounded-3xl p-8 shadow-2xl">
        @csrf

        <!-- Customer Section -->
        <div>
            <h3 class="text-sm font-bold uppercase tracking-wider text-blue-400 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center text-xs">1</span>
                Customer Profile & Endpoint Geolocation
            </h3>

            <div class="space-y-4">
                @if($customers->count() > 0)
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Select Existing Customer (Optional)</label>
                        <select name="customer_id" id="customer_id" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                            <option value="">-- Or enter new customer details below --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->district ?? 'No district' }} - {{ $c->thana ?? '' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="grid md:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Customer / Company Name *</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="e.g. Bangladesh Bank Motijheel Annex"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Phone Number</label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="+8801..."
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Endpoint Physical Address</label>
                        <input type="text" name="customer_address" value="{{ old('customer_address') }}" placeholder="Street, Building, Floor, Room..."
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">District</label>
                        <input type="text" name="district" value="{{ old('district', 'Dhaka') }}"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Thana / Upazila</label>
                        <input type="text" name="thana" value="{{ old('thana', 'Motijheel') }}"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Latitude</label>
                        <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude', '23.7297430') }}" placeholder="23.xxxxx"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Longitude</label>
                        <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude', '90.4174310') }}" placeholder="90.xxxxx"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                    </div>
                </div>
            </div>
        </div>

        <hr class="border-slate-800">

        <!-- Technical Transmission Specifications -->
        <div>
            <h3 class="text-sm font-bold uppercase tracking-wider text-cyan-400 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center text-xs">2</span>
                Transmission & Bandwidth Specifications
            </h3>

            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Requested Bandwidth (Mbps) *</label>
                    <input type="number" name="bandwidth_mbps" value="{{ old('bandwidth_mbps', 200) }}" min="1" required
                        class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono font-bold focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Service Type *</label>
                    <select name="service_type" required class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="Data Link" {{ old('service_type', 'Data Link') === 'Data Link' ? 'selected' : '' }}>Data Link</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Preferred NTTN Provider</label>
                    <select name="preferred_nttn" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="Any Qualified NTTN">Any Qualified NTTN (Best Route)</option>
                        <option value="Fiber@Home" {{ old('preferred_nttn') === 'Fiber@Home' ? 'selected' : '' }}>Fiber@Home</option>
                        <option value="Summit Communications" {{ old('preferred_nttn') === 'Summit Communications' ? 'selected' : '' }}>Summit Communications</option>
                        <option value="Bahon Limited" {{ old('preferred_nttn') === 'Bahon Limited' ? 'selected' : '' }}>Bahon Limited</option>
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Special Technical Instructions / Route Notes</label>
                    <textarea name="description" rows="3" placeholder="Provide any existing fiber termination points, building POP notes, or redundancy requirements..."
                        class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <div class="pt-4 flex items-center justify-end gap-3">
            <a href="{{ route('surveys.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-sm font-bold shadow-lg shadow-blue-500/25 transition-all">
                Lodge Survey Request &rarr;
            </button>
        </div>
    </form>

</div>
@endsection
