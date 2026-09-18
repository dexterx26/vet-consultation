@extends('layouts.app')

@section('title', 'Find Veterinarians')

@section('content')
<div class="space-y-6">
    
    <!-- Proximity Search Header -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-6 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 flex items-center">
                    <i class="fa-solid fa-location-dot text-brand-600 mr-2.5"></i> Find Veterinarians Near You
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Showing available licensed veterinarians. Location suggestions based on proximity to 
                    <strong class="text-slate-700">{{ $clientProfile->city ?? 'Quezon City' }}</strong>.
                </p>
            </div>
            <div class="text-xs bg-brand-50 text-brand-800 px-3.5 py-2 rounded-xl border border-brand-200/60 font-medium">
                <i class="fa-solid fa-earth-asia text-brand-600 mr-1.5"></i> Proximity Search Active (Haversine Formula)
            </div>
        </div>

        <!-- Filter Controls Form -->
        <form method="GET" action="{{ route('client.vets.search') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Animal Category</label>
                <select name="animal_type" class="w-full rounded-xl border-slate-200 text-xs py-2.5 focus:ring-brand-500 focus:border-brand-500">
                    <option value="">All Animals</option>
                    @foreach($animalTypes as $type)
                        <option value="{{ $type->name }}" {{ request('animal_type') == $type->name ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Max Consultation Fee (₱)</label>
                <input type="number" name="max_fee" value="{{ request('max_fee') }}" placeholder="e.g. 1000" class="w-full rounded-xl border-slate-200 text-xs py-2.5 focus:ring-brand-500 focus:border-brand-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Search Keyword</label>
                <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Name, expertise, clinic..." class="w-full rounded-xl border-slate-200 text-xs py-2.5 focus:ring-brand-500 focus:border-brand-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Sort Results By</label>
                <div class="flex items-center space-x-2">
                    <select name="sort" class="w-full rounded-xl border-slate-200 text-xs py-2.5 focus:ring-brand-500 focus:border-brand-500">
                        <option value="distance" {{ request('sort') == 'distance' ? 'selected' : '' }}>Distance (Closest first)</option>
                        <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Highest Rating</option>
                        <option value="fee_asc" {{ request('sort') == 'fee_asc' ? 'selected' : '' }}>Lowest Fee</option>
                    </select>
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 rounded-xl font-semibold text-xs shadow-sm">
                        Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Veterinarians List Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($vets as $vet)
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-all p-6 flex flex-col justify-between relative">
                
                <!-- Distance Badge -->
                <div class="absolute top-4 right-4 bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-full text-[11px] font-bold flex items-center space-x-1">
                    <i class="fa-solid fa-location-arrow"></i>
                    <span>{{ $vet->distance_km }} km away</span>
                </div>

                <div>
                    <!-- Vet Info -->
                    <div class="flex items-start space-x-4 mb-4">
                        <div class="w-14 h-14 rounded-2xl bg-slate-800 text-brand-400 font-bold text-xl flex items-center justify-center shrink-0 border border-slate-700">
                            {{ strtoupper(substr($vet->name, 4, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1 pr-16">
                            <h3 class="font-bold text-slate-800 text-base leading-snug hover:text-brand-600 transition-colors">{{ $vet->name }}</h3>
                            <p class="text-xs text-slate-500 font-medium truncate">{{ $vet->vetProfile->clinic_name ?: 'Private Teleconsult Clinic' }}</p>
                            
                            <div class="flex items-center space-x-2 mt-1 text-xs">
                                <span class="text-amber-500 font-bold flex items-center">
                                    <i class="fa-solid fa-star mr-1"></i> {{ number_format($vet->vetProfile->average_rating ?? 5.0, 1) }}
                                </span>
                                <span class="text-slate-300">•</span>
                                <span class="text-slate-600 font-medium">{{ $vet->vetProfile->years_experience ?? 1 }} yrs exp</span>
                            </div>
                        </div>
                    </div>

                    <!-- Specializations & Fee -->
                    <div class="space-y-2 text-xs border-t border-slate-100 pt-3">
                        <p class="text-slate-600">
                            <strong class="text-slate-800">Specialities:</strong> {{ Str::limit($vet->vetProfile->expertise ?? 'Small Animal Care', 80) }}
                        </p>

                        <div class="flex items-center justify-between bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <span class="text-slate-500 font-medium">Consultation Fee</span>
                            <span class="font-extrabold text-brand-700 text-sm">₱{{ number_format($vet->vetProfile->consultation_fee ?? 500, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-3 border-t border-slate-100 flex items-center space-x-2">
                    <a href="{{ route('client.bookings.create', ['vet_id' => $vet->id]) }}" class="flex-1 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold py-2.5 px-3 rounded-xl shadow-md shadow-brand-600/20 text-center transition-all">
                        Request Consultation
                    </a>
                    <a href="{{ route('client.vets.show', $vet) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold py-2.5 px-3 rounded-xl transition-all">
                        Profile
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-500">
                <i class="fa-solid fa-user-doctor text-4xl text-slate-300 mb-3"></i>
                <h3 class="font-bold text-slate-700">No Veterinarians Found</h3>
                <p class="text-xs mt-1">Try adjusting your filters or search keywords.</p>
            </div>
        @endforelse
    </div>

</div>
@endsection
