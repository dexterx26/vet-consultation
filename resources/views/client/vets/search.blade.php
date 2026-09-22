@extends('layouts.app')

@section('title', 'Find Veterinarians')

@section('content')
<div 
    x-data="{
        profileModalOpen: false,
        selectedVet: null,
        openProfileModal(vet) {
            this.selectedVet = vet;
            this.profileModalOpen = true;
            document.body.classList.add('overflow-hidden');
        },
        closeProfileModal() {
            this.profileModalOpen = false;
            document.body.classList.remove('overflow-hidden');
        }
    }"
    @keydown.escape.window="closeProfileModal()"
    class="space-y-6"
>
    
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

        <!-- Selected Pet Banner -->
        @if(request('pet_id') && ($selectedPet = \App\Models\Pet::where('user_id', auth()->id())->find(request('pet_id'))))
            <div class="mb-6 bg-brand-50/70 border border-brand-200 text-brand-900 rounded-2xl p-4 flex items-center justify-between text-xs">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-brand-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                        <i class="fa-solid fa-paw"></i>
                    </div>
                    <div>
                        <span class="font-bold text-sm text-slate-800">Booking for: {{ $selectedPet->name }}</span>
                        <p class="text-slate-600 text-[11px]">{{ $selectedPet->animalType->name ?? 'Pet' }} • {{ $selectedPet->breed_name }} — Finding veterinarians who handle this animal category</p>
                    </div>
                </div>
                <a href="{{ route('client.vets.search') }}" class="text-xs text-slate-500 hover:text-slate-700 underline font-medium">Clear Pet Filter</a>
            </div>
        @endif

        <!-- Filter Controls Form -->
        <form method="GET" action="{{ route('client.vets.search') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <input type="hidden" name="pet_id" value="{{ request('pet_id') }}">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Animal Category</label>
                <select name="animal_type" class="w-full rounded-xl border-slate-200 text-xs py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500">
                    <option value="">All Animals</option>
                    @foreach($animalTypes as $type)
                        <option value="{{ $type->name }}" {{ request('animal_type') == $type->name ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Max Consultation Fee (₱)</label>
                <input type="number" name="max_fee" value="{{ request('max_fee') }}" placeholder="e.g. 1000" class="w-full rounded-xl border-slate-200 text-xs py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Search Keyword</label>
                <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Name, expertise, clinic..." class="w-full rounded-xl border-slate-200 text-xs py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Sort Results By</label>
                <div class="flex items-center space-x-2">
                    <select name="sort" class="w-full rounded-xl border-slate-200 text-xs py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500">
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
            @php
                $cleanedName = preg_replace('/^Dr\.\s*/i', '', $vet->name);
                $initial = strtoupper(mb_substr($cleanedName, 0, 1)) ?: 'V';
                $rawAnimals = $vet->vetProfile->animals_handled;
                if (is_array($rawAnimals)) {
                    $animalsHandled = $rawAnimals;
                } elseif (is_string($rawAnimals) && !empty($rawAnimals)) {
                    $decoded = json_decode($rawAnimals, true);
                    $animalsHandled = is_array($decoded) ? $decoded : array_map('trim', explode(',', $rawAnimals));
                } else {
                    $animalsHandled = [];
                }

                $vetModalData = [
                    'id' => $vet->id,
                    'name' => $vet->name,
                    'initial' => $initial,
                    'clinic_name' => $vet->vetProfile->clinic_name ?: 'Private Teleconsult Clinic',
                    'clinic_address' => $vet->vetProfile->clinic_address ?: (($vet->vetProfile->city ? $vet->vetProfile->city . ', ' : '') . ($vet->vetProfile->province ?? '')),
                    'city' => $vet->vetProfile->city ?? '',
                    'province' => $vet->vetProfile->province ?? '',
                    'distance_km' => $vet->distance_km ?? 5.0,
                    'rating' => number_format($vet->vetProfile->average_rating ?? 5.0, 1),
                    'reviews_count' => $vet->vetProfile->reviews ? $vet->vetProfile->reviews->count() : 0,
                    'years_experience' => $vet->vetProfile->years_experience ?? 1,
                    'languages' => $vet->vetProfile->languages ?? 'English, Filipino',
                    'consultation_fee' => number_format($vet->vetProfile->consultation_fee ?? 500, 2),
                    'additional_pet_fee' => number_format($vet->vetProfile->effective_additional_pet_fee, 2),
                    'additional_pet_duration' => $vet->vetProfile->effective_additional_pet_duration,
                    'bio' => $vet->vetProfile->bio ?: 'No biography details provided.',
                    'expertise' => $vet->vetProfile->expertise ?: 'Small Animal Care & General Veterinary Medicine',
                    'license_number' => $vet->vetProfile->license_number ?: 'N/A',
                    'animals_handled' => $animalsHandled,
                    'booking_url' => route('client.bookings.create', ['vet_id' => $vet->id, 'pet_id' => request('pet_id')]),
                    'reviews' => $vet->vetProfile->reviews ? $vet->vetProfile->reviews->map(function($rev) {
                        return [
                            'client_name' => $rev->client->name ?? 'Verified Client',
                            'rating' => (int) $rev->rating,
                            'comment' => $rev->comment,
                            'date' => $rev->created_at ? $rev->created_at->diffForHumans() : '',
                        ];
                    })->values() : [],
                ];
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-all p-6 flex flex-col justify-between relative">
                
                <!-- Distance Badge -->
                <div class="absolute top-4 right-4 bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-full text-[11px] font-bold flex items-center space-x-1">
                    <i class="fa-solid fa-location-arrow"></i>
                    <span>{{ $vet->distance_km }} km away</span>
                </div>

                <div>
                    <!-- Vet Info -->
                    <div class="flex items-start space-x-4 mb-4">
                        <div @click="openProfileModal(@js($vetModalData))" class="w-14 h-14 rounded-2xl bg-slate-800 text-brand-400 font-bold text-xl flex items-center justify-center shrink-0 border border-slate-700 cursor-pointer hover:border-brand-500 hover:scale-105 transition-all" title="View Profile">
                            {{ strtoupper(substr($vet->name, 4, 1)) ?: $initial }}
                        </div>
                        <div class="min-w-0 flex-1 pr-16">
                            <h3 @click="openProfileModal(@js($vetModalData))" class="font-bold text-slate-800 text-base leading-snug hover:text-brand-600 transition-colors cursor-pointer" title="View Profile">{{ $vet->name }}</h3>
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

                        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Consultation Fee</span>
                                <span class="font-extrabold text-brand-700 text-sm">₱{{ number_format($vet->vetProfile->consultation_fee ?? 500, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-200/50 pt-1">
                                <span>Extra Pet Rate</span>
                                <span class="font-semibold text-slate-700">+₱{{ number_format($vet->vetProfile->effective_additional_pet_fee, 2) }} (+{{ $vet->vetProfile->effective_additional_pet_duration }}m)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-3 border-t border-slate-100 flex items-center space-x-2">
                    <a href="{{ route('client.bookings.create', ['vet_id' => $vet->id, 'pet_id' => request('pet_id')]) }}" class="flex-1 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold py-2.5 px-3 rounded-xl shadow-md shadow-brand-600/20 text-center transition-all">
                        Request Consultation
                    </a>
                    <button type="button" @click="openProfileModal(@js($vetModalData))" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold py-2.5 px-3.5 rounded-xl transition-all flex items-center space-x-1.5 cursor-pointer">
                        <i class="fa-regular fa-id-badge text-slate-500"></i>
                        <span>Profile</span>
                    </button>
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

    <!-- Veterinarian Profile Modal -->
    <div 
        x-show="profileModalOpen" 
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm p-3 sm:p-6 flex items-center justify-center"
        style="display: none;"
        aria-modal="true" 
        role="dialog"
    >
        <div 
            @click.outside="closeProfileModal()" 
            x-show="profileModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-3"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-3"
            class="bg-white rounded-3xl shadow-2xl border border-slate-200/90 w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden relative text-slate-800"
        >
            <template x-if="selectedVet">
                <div class="flex flex-col h-full overflow-hidden">
                    
                    <!-- Modal Header -->
                    <div class="bg-slate-900 text-white p-5 sm:p-6 relative flex items-start justify-between border-b border-slate-800">
                        <div class="flex items-center space-x-4 pr-8">
                            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-slate-800 text-brand-400 font-extrabold text-2xl flex items-center justify-center shrink-0 border border-slate-700 shadow-md">
                                <span x-text="selectedVet.initial"></span>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-lg sm:text-xl font-bold text-white tracking-tight" x-text="selectedVet.name"></h2>
                                    <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[11px] font-bold px-2 py-0.5 rounded-full flex items-center space-x-1">
                                        <i class="fa-solid fa-circle-check text-[10px]"></i>
                                        <span>Verified Vet</span>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-300 mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span x-text="selectedVet.clinic_name"></span>
                                    <span x-show="selectedVet.city">•</span>
                                    <span x-text="selectedVet.city + (selectedVet.province ? ', ' + selectedVet.province : '')"></span>
                                </p>
                                <div class="flex flex-wrap items-center gap-2.5 mt-2 text-xs">
                                    <span class="bg-amber-400/20 text-amber-300 border border-amber-400/30 px-2 py-0.5 rounded-lg font-bold flex items-center text-[11px]">
                                        <i class="fa-solid fa-star text-amber-400 mr-1 text-[10px]"></i>
                                        <span x-text="selectedVet.rating"></span> Rating
                                    </span>
                                    <span class="text-slate-300 text-[11px] flex items-center">
                                        <i class="fa-solid fa-briefcase text-slate-400 mr-1"></i>
                                        <span x-text="selectedVet.years_experience + ' yrs exp'"></span>
                                    </span>
                                    <span class="text-slate-300 text-[11px] flex items-center">
                                        <i class="fa-solid fa-location-arrow text-emerald-400 mr-1"></i>
                                        <span x-text="selectedVet.distance_km + ' km away'"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Close Button -->
                        <button 
                            type="button" 
                            @click="closeProfileModal()" 
                            class="w-8 h-8 rounded-full bg-slate-800/80 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-colors shrink-0 cursor-pointer"
                            aria-label="Close Profile"
                        >
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <!-- Modal Scrollable Content -->
                    <div class="overflow-y-auto custom-scrollbar p-5 sm:p-6 space-y-5 flex-1 text-slate-700 text-xs">
                        
                        <!-- Consultation Fee Box -->
                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Base Consultation Fee</span>
                                <span class="text-2xl font-black text-brand-700 block">₱<span x-text="selectedVet.consultation_fee"></span></span>
                                <span class="text-[11px] text-slate-500">Regular single-pet teleconsultation</span>
                            </div>
                            <div class="sm:border-l sm:border-slate-200/80 sm:pl-4 space-y-1">
                                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Additional Pet Rate</span>
                                <span class="text-base font-bold text-slate-800 block">+₱<span x-text="selectedVet.additional_pet_fee"></span></span>
                                <span class="text-[11px] text-slate-500">+<span x-text="selectedVet.additional_pet_duration"></span> mins extended consult per extra pet</span>
                            </div>
                        </div>

                        <!-- About / Bio -->
                        <div class="space-y-2">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center">
                                <i class="fa-regular fa-user text-brand-600 mr-2"></i> About Veterinarian
                            </h3>
                            <p class="text-slate-600 leading-relaxed bg-slate-50/60 p-3.5 rounded-xl border border-slate-100 whitespace-pre-line" x-text="selectedVet.bio"></p>
                        </div>

                        <!-- Specializations & Animals -->
                        <div class="space-y-3">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center">
                                <i class="fa-solid fa-stethoscope text-brand-600 mr-2"></i> Specializations & Practice
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50/60 p-3.5 rounded-xl border border-slate-100">
                                <div>
                                    <span class="text-slate-400 font-semibold block uppercase text-[10px] tracking-wider">Expertise</span>
                                    <span class="font-medium text-slate-700" x-text="selectedVet.expertise"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-semibold block uppercase text-[10px] tracking-wider">License Number</span>
                                    <code class="bg-white px-2 py-0.5 rounded text-slate-800 font-mono text-[11px] border border-slate-200" x-text="selectedVet.license_number"></code>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-semibold block uppercase text-[10px] tracking-wider">Languages</span>
                                    <span class="font-medium text-slate-700" x-text="selectedVet.languages"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-semibold block uppercase text-[10px] tracking-wider">Clinic Address</span>
                                    <span class="font-medium text-slate-700" x-text="selectedVet.clinic_address"></span>
                                </div>
                            </div>

                            <!-- Animals Handled -->
                            <div>
                                <span class="text-slate-500 font-semibold text-[11px] block uppercase tracking-wider mb-2">Animals Handled</span>
                                <div class="flex flex-wrap gap-2">
                                    <template x-if="selectedVet.animals_handled && selectedVet.animals_handled.length > 0">
                                        <template x-for="animal in selectedVet.animals_handled" :key="animal">
                                            <span class="bg-brand-50 text-brand-700 border border-brand-200 text-xs font-semibold px-3 py-1 rounded-xl flex items-center space-x-1">
                                                <i class="fa-solid fa-paw text-[10px]"></i>
                                                <span x-text="animal"></span>
                                            </span>
                                        </template>
                                    </template>
                                    <template x-if="!selectedVet.animals_handled || selectedVet.animals_handled.length === 0">
                                        <span class="text-slate-400 italic">General practice (all domestic animals)</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Client Reviews -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-800 flex items-center">
                                    <i class="fa-regular fa-star text-amber-500 mr-2"></i> Client Reviews
                                    <span class="ml-2 text-xs font-semibold text-slate-500" x-text="'(' + selectedVet.reviews_count + ')'"></span>
                                </h3>
                                <span class="text-xs text-amber-600 font-bold flex items-center">
                                    <i class="fa-solid fa-star text-amber-400 mr-1"></i>
                                    <span x-text="selectedVet.rating"></span> / 5.0
                                </span>
                            </div>

                            <div class="space-y-2.5">
                                <template x-if="selectedVet.reviews && selectedVet.reviews.length > 0">
                                    <template x-for="(review, index) in selectedVet.reviews" :key="index">
                                        <div class="bg-slate-50/70 p-3 rounded-xl border border-slate-100 space-y-1">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-bold text-slate-800" x-text="review.client_name"></span>
                                                <div class="flex items-center text-amber-500 text-[10px]">
                                                    <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                                                        <i class="fa-solid fa-star" :class="star <= review.rating ? 'text-amber-400' : 'text-slate-200'"></i>
                                                    </template>
                                                </div>
                                            </div>
                                            <p class="text-xs text-slate-600 italic" x-text="'“' + review.comment + '”'"></p>
                                            <template x-if="review.date">
                                                <span class="text-[10px] text-slate-400 block pt-0.5" x-text="review.date"></span>
                                            </template>
                                        </div>
                                    </template>
                                </template>
                                <template x-if="!selectedVet.reviews || selectedVet.reviews.length === 0">
                                    <div class="bg-slate-50 rounded-xl p-4 text-center text-slate-400 italic">
                                        No reviews recorded yet for this veterinarian.
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-slate-50 border-t border-slate-200/80 p-4 px-6 flex items-center justify-between gap-3">
                        <button 
                            type="button" 
                            @click="closeProfileModal()" 
                            class="bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 font-semibold text-xs py-2.5 px-4 rounded-xl transition-all cursor-pointer"
                        >
                            Close
                        </button>
                        <a 
                            :href="selectedVet.booking_url" 
                            class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs py-2.5 px-5 rounded-xl shadow-md shadow-brand-600/20 transition-all flex items-center space-x-2"
                        >
                            <i class="fa-solid fa-calendar-check"></i>
                            <span>Request Consultation</span>
                        </a>
                    </div>

                </div>
            </template>
        </div>
    </div>

</div>
@endsection
