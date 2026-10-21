@extends('layouts.app', ['title' => 'Edit Survey ' . $survey->request_no, 'header' => 'Edit & Revise Survey: ' . $survey->request_no])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-extrabold text-white">Revise Survey Request</h2>
            <p class="text-xs text-slate-400 mt-1">Update endpoint parameters or requested specifications to re-trigger transmission engineering analysis.</p>
        </div>
        <a href="{{ route('surveys.show', $survey) }}" class="px-4 py-2 bg-slate-900 border border-slate-800 text-slate-300 hover:text-white rounded-xl text-xs font-semibold">
            &larr; Back to Survey Details
        </a>
    </div>

    @if($survey->status === 'NOT_FEASIBLE')
        <!-- Transmission Infeasibility Notice -->
        <div class="p-5 rounded-3xl bg-rose-950/40 border border-rose-500/30 text-xs space-y-3 shadow-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-rose-500/20 text-rose-400 flex items-center justify-center text-xs font-bold">⚠️</span>
                    <h3 class="font-bold text-white text-sm">Transmission Feasibility Assessment: Infeasible Route</h3>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                    STATUS: NOT_FEASIBLE
                </span>
            </div>
            <p class="text-slate-300">
                The transmission team evaluated this route and determined it is not feasible with the current parameters (duct/capacity block or out of reach). Please modify the address, geo-coordinates, or carrier preference below to request a fresh survey.
            </p>
            @if($survey->feedbacks->count() > 0)
                <div class="grid sm:grid-cols-2 gap-3 pt-1">
                    @foreach($survey->feedbacks as $fb)
                        <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 text-slate-300">
                            <div class="flex items-center justify-between font-mono font-bold text-xs mb-1">
                                <span class="text-cyan-400">{{ $fb->provider }}</span>
                                <span class="text-rose-400 font-bold">NOT FEASIBLE</span>
                            </div>
                            <div class="text-[11px] text-slate-400">
                                Nearest POP: <strong class="text-slate-200">{{ $fb->nearest_pop }}</strong> ({{ $fb->fiber_distance_km }} km)
                            </div>
                            @if($fb->feedback)
                                <div class="text-[11px] text-amber-300/90 italic mt-1">
                                    Notes: "{{ $fb->feedback }}"
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('surveys.update', $survey) }}" class="space-y-6 bg-slate-900/80 border border-slate-800 rounded-3xl p-8 shadow-2xl">
        @csrf
        @method('PUT')

        <!-- Customer Section -->
        <div>
            <h3 class="text-sm font-bold uppercase tracking-wider text-blue-400 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center text-xs">1</span>
                Customer Profile & Endpoint Geolocation
            </h3>

            <div class="space-y-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Customer / Company Name *</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name', $survey->customer?->name) }}" required
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Phone Number</label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', $survey->customer?->phone) }}" placeholder="+8801..."
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Endpoint Physical Address *</label>
                        <input type="text" name="customer_address" value="{{ old('customer_address', $survey->customer?->address) }}" placeholder="Street, Building, Floor, Room..."
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">District</label>
                        <input type="text" name="district" value="{{ old('district', $survey->customer?->district ?? 'Dhaka') }}"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Thana / Upazila</label>
                        <input type="text" name="thana" value="{{ old('thana', $survey->customer?->thana ?? 'Motijheel') }}"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Latitude (Lat)</label>
                        <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude', $survey->customer?->latitude) }}" placeholder="23.xxxxx"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Longitude (Long)</label>
                        <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude', $survey->customer?->longitude) }}" placeholder="90.xxxxx"
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
                    <input type="number" name="bandwidth_mbps" value="{{ old('bandwidth_mbps', $survey->bandwidth_mbps) }}" min="1" required
                        class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono font-bold focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Service Type *</label>
                    <select name="service_type" required class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="Data Link" {{ old('service_type', $survey->service_type) === 'Data Link' ? 'selected' : '' }}>Data Link</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Preferred NTTN Provider</label>
                    <select name="preferred_nttn" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="Any Qualified NTTN" {{ old('preferred_nttn', $survey->preferred_nttn) === 'Any Qualified NTTN' ? 'selected' : '' }}>Any Qualified NTTN (Best Route)</option>
                        <option value="Fiber@Home" {{ old('preferred_nttn', $survey->preferred_nttn) === 'Fiber@Home' ? 'selected' : '' }}>Fiber@Home</option>
                        <option value="Summit Communications" {{ old('preferred_nttn', $survey->preferred_nttn) === 'Summit Communications' ? 'selected' : '' }}>Summit Communications</option>
                        <option value="Bahon Limited" {{ old('preferred_nttn', $survey->preferred_nttn) === 'Bahon Limited' ? 'selected' : '' }}>Bahon Limited</option>
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Special Technical Instructions / Route Notes</label>
                    <textarea name="description" rows="3" placeholder="Provide any updated fiber termination points, building POP notes, or revised requirements..."
                        class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">{{ old('description', $survey->description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-800">
            <a href="{{ route('surveys.show', $survey) }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold transition-colors">
                Cancel
            </a>
            <div class="flex items-center gap-3">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-sm font-bold shadow-lg shadow-blue-500/25 transition-all">
                    Update & Re-submit Survey &rarr;
                </button>
            </div>
        </div>
    </form>

</div>
@endsection
