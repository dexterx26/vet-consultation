@extends('layouts.app')

@section('title', $vet->name . ' — Profile')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Profile Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-8 shadow-sm">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="flex items-center space-x-5">
                <div class="w-20 h-20 rounded-2xl bg-navy-800 text-brand-400 font-extrabold text-3xl flex items-center justify-center shrink-0 border border-slate-700 shadow-md">
                    {{ strtoupper(substr($vet->name, 4, 1)) }}
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold text-slate-800">{{ $vet->name }}</h1>
                        <span class="bg-emerald-100 text-emerald-800 text-[11px] font-extrabold px-2.5 py-0.5 rounded-full flex items-center space-x-1">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Verified Vet</span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 font-medium mt-1">{{ $vet->vetProfile->clinic_name ?: 'Private Teleconsult Clinic' }} • {{ $vet->vetProfile->city }}, {{ $vet->vetProfile->province }}</p>
                    <div class="flex items-center space-x-4 mt-3 text-xs">
                        <span class="bg-amber-50 text-amber-700 font-bold px-2.5 py-1 rounded-lg border border-amber-200">
                            <i class="fa-solid fa-star text-amber-500 mr-1"></i> {{ number_format($vet->vetProfile->average_rating, 1) }} Rating
                        </span>
                        <span class="text-slate-600 font-medium"><i class="fa-solid fa-briefcase text-slate-400 mr-1"></i> {{ $vet->vetProfile->years_experience }} Years Exp</span>
                        <span class="text-slate-600 font-medium"><i class="fa-solid fa-language text-slate-400 mr-1"></i> {{ $vet->vetProfile->languages }}</span>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-auto bg-slate-50 p-4 rounded-xl border border-slate-200 text-center">
                <span class="block text-xs text-slate-500 font-medium">Consultation Fee</span>
                <span class="block text-2xl font-black text-brand-700 mt-0.5">₱{{ number_format($vet->vetProfile->consultation_fee, 2) }}</span>
                <a href="{{ route('client.bookings.create', ['vet_id' => $vet->id]) }}" class="mt-3 block w-full bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-md shadow-brand-600/30 transition-all">
                    Book Consultation Now
                </a>
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols -->
        <div class="md:col-span-2 space-y-6">
            <!-- Biography -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-800 mb-3">About Dr. {{ $vet->name }}</h2>
                <p class="text-xs text-slate-600 leading-relaxed">{{ $vet->vetProfile->bio ?: 'No bio details provided.' }}</p>
            </div>

            <!-- Expertise & Animals -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <h2 class="text-lg font-bold text-slate-800">Specializations & Animal Types</h2>
                <div class="text-xs text-slate-700 space-y-2">
                    <p><strong>Areas of Expertise:</strong> {{ $vet->vetProfile->expertise }}</p>
                    <p><strong>License Number:</strong> <code class="bg-slate-100 px-2 py-0.5 rounded text-slate-800 font-mono">{{ $vet->vetProfile->license_number }}</code></p>
                </div>

                <div class="pt-2">
                    <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Animals Handled</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($vet->vetProfile->animals_handled ?? [] as $animal)
                            <span class="bg-brand-50 text-brand-700 border border-brand-200 text-xs font-semibold px-3 py-1 rounded-xl">
                                <i class="fa-solid fa-paw mr-1 text-[10px]"></i> {{ $animal }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Patient Reviews -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <h2 class="text-lg font-bold text-slate-800">Client Reviews</h2>
                @forelse($vet->vetProfile->reviews as $review)
                    <div class="border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                        <div class="flex items-center justify-between text-xs">
                            <strong class="text-slate-800">{{ $review->client->name ?? 'Verified Client' }}</strong>
                            <span class="text-amber-500 font-bold"><i class="fa-solid fa-star"></i> {{ $review->rating }}/5</span>
                        </div>
                        <p class="text-xs text-slate-600 italic mt-1 bg-slate-50 p-2.5 rounded-xl border border-slate-100">"{{ $review->comment }}"</p>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic">No reviews submitted yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Right Col -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <h2 class="text-base font-bold text-slate-800">Clinic Location</h2>
                <div class="text-xs text-slate-600 space-y-2">
                    <p><i class="fa-solid fa-hospital text-brand-600 mr-1.5"></i> {{ $vet->vetProfile->clinic_name ?: 'Online Teleconsult' }}</p>
                    <p><i class="fa-solid fa-location-dot text-brand-600 mr-1.5"></i> {{ $vet->vetProfile->clinic_address ?: ($vet->vetProfile->city . ', ' . $vet->vetProfile->province) }}</p>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
