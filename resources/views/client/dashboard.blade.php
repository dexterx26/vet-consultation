@extends('layouts.app')

@section('title', 'Client Dashboard')

@section('content')
<div 
    x-data="{
        petModalOpen: false,
        selectedPet: null,
        openPetModal(pet) {
            this.selectedPet = pet;
            this.petModalOpen = true;
            document.body.classList.add('overflow-hidden');
        },
        closePetModal() {
            this.petModalOpen = false;
            document.body.classList.remove('overflow-hidden');
        }
    }"
    @keydown.escape.window="closePetModal()"
    class="space-y-8"
>
    
    <!-- Welcome Header Banner -->
    <div class="bg-gradient-to-r from-navy-800 via-slate-900 to-brand-900 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
        <div class="absolute right-0 top-0 bottom-0 w-1/3 bg-white/5 skew-x-12 pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="text-xs uppercase tracking-widest font-semibold text-brand-400 bg-brand-950/60 px-3 py-1 rounded-full border border-brand-500/30">Pet Owner Portal</span>
                <h1 class="text-3xl font-extrabold text-white mt-2">Welcome back, {{ auth()->user()->name }}!</h1>
                <p class="text-slate-300 text-sm mt-1 max-w-xl">
                    Connect with top-rated licensed veterinarians, schedule remote consultations, and keep track of your pets' medical records in one place.
                </p>

                <!-- Credits Pill -->
                <div class="mt-4 inline-flex items-center space-x-3 bg-white/10 backdrop-blur-md px-4 py-2 rounded-2xl border border-white/15 text-xs">
                    <span class="flex items-center text-amber-300 font-extrabold font-mono text-sm">
                        <i class="fa-solid fa-coins mr-1.5 text-amber-400"></i>
                        {{ number_format(auth()->user()->credits ?? 0) }} Credits Available
                    </span>
                    <span class="text-slate-400">•</span>
                    <span class="text-slate-300 text-[11px]">300 credits per consultation booking</span>
                </div>
            </div>
            <div class="flex items-center space-x-3 shrink-0">
                <a href="{{ route('client.vets.search') }}" class="bg-brand-500 hover:bg-brand-400 text-white font-semibold px-5 py-3 rounded-xl shadow-lg shadow-brand-500/30 transition-all flex items-center space-x-2 text-sm">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Find a Veterinarian</span>
                </a>
                <a href="{{ route('client.pets.create') }}" class="bg-white/10 hover:bg-white/20 text-white font-semibold px-4 py-3 rounded-xl backdrop-blur transition-all flex items-center space-x-2 text-sm border border-white/20">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add Pet</span>
                </a>
            </div>
        </div>
    </div>

    <!-- My Registered Pets Row -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-slate-800 flex items-center">
                <i class="fa-solid fa-paw text-brand-600 mr-2.5"></i> My Registered Pets
            </h2>
            <a href="{{ route('client.pets.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">View All Pets →</a>
        </div>

        @if($pets->isEmpty())
            <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">
                <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-dog text-xl"></i>
                </div>
                <h3 class="font-bold text-slate-700">No Pets Registered Yet</h3>
                <p class="text-xs mt-1">Add your pet to easily request teleconsultations and store medical notes.</p>
                <a href="{{ route('client.pets.create') }}" class="inline-flex items-center space-x-2 bg-brand-600 text-white text-xs font-semibold px-4 py-2 rounded-xl mt-4 shadow-sm">
                    <i class="fa-solid fa-plus"></i> <span>Add First Pet</span>
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($pets as $pet)
                    @php
                        $petModalData = [
                            'id' => $pet->id,
                            'name' => $pet->name,
                            'photo_url' => $pet->photo ? asset('storage/' . $pet->photo) : null,
                            'animal_type' => $pet->animalType ? $pet->animalType->name : 'Pet',
                            'animal_icon' => $pet->animalType ? $pet->animalType->icon : 'fa-paw',
                            'breed_name' => $pet->breed_name ?: 'Unknown Breed',
                            'sex' => $pet->sex ?: 'Unknown',
                            'dob' => $pet->dob ? $pet->dob->format('M d, Y') : null,
                            'age_text' => $pet->age_text ?: 'Not specified',
                            'weight' => $pet->weight ?: 'Not specified',
                            'color' => $pet->color ?: 'Not specified',
                            'allergies' => $pet->allergies ?: 'No known allergies recorded.',
                            'existing_conditions' => $pet->existing_conditions ?: 'No chronic or existing conditions recorded.',
                            'vaccination_info' => $pet->vaccination_info ?: 'No vaccination history added yet.',
                            'book_url' => route('client.vets.search', ['animal_type' => $pet->animalType->name ?? '', 'pet_id' => $pet->id]),
                            'edit_url' => route('client.pets.edit', $pet),
                            'consultations' => $pet->consultations ? $pet->consultations->map(function($consult) {
                                return [
                                    'id' => $consult->id,
                                    'consultation_number' => $consult->consultation_number,
                                    'vet_name' => $consult->vet ? $consult->vet->name : 'Assigned Veterinarian',
                                    'status' => ucfirst(str_replace('_', ' ', $consult->status)),
                                    'status_raw' => $consult->status,
                                    'date' => $consult->scheduled_at ? $consult->scheduled_at->format('M d, Y @ g:i A') : '',
                                    'has_record' => (bool) $consult->record,
                                    'record_url' => route('client.bookings.show', $consult),
                                ];
                            })->values() : [],
                        ];
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-all group relative">
                        <div class="flex items-center space-x-4">
                            <div 
                                @click="openPetModal(@js($petModalData))" 
                                class="w-14 h-14 rounded-2xl bg-brand-50 border border-brand-100 flex items-center justify-center text-brand-600 font-bold text-xl shrink-0 overflow-hidden cursor-pointer hover:scale-105 transition-all shadow-sm"
                                title="View Pet Details"
                            >
                                @if($pet->photo)
                                    <img src="{{ asset('storage/' . $pet->photo) }}" class="w-full h-full object-cover">
                                @else
                                    <i class="fa-solid {{ $pet->animalType ? $pet->animalType->icon : 'fa-paw' }}"></i>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 
                                    @click="openPetModal(@js($petModalData))" 
                                    class="font-bold text-slate-800 text-base group-hover:text-brand-600 transition-colors truncate cursor-pointer"
                                    title="View Pet Details"
                                >
                                    {{ $pet->name }}
                                </h3>
                                <p class="text-xs text-slate-500 capitalize">{{ $pet->animalType->name ?? 'Pet' }} • {{ $pet->breed_name }}</p>
                                <span class="inline-block text-[10px] bg-slate-100 text-slate-600 font-semibold px-2 py-0.5 rounded mt-1.5">{{ $pet->sex }} • {{ $pet->age_text ?: 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <a href="{{ route('client.vets.search', ['animal_type' => $pet->animalType->name ?? '', 'pet_id' => $pet->id]) }}" class="text-brand-600 font-semibold hover:underline flex items-center space-x-1">
                                <span>Book Vet</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                            <button 
                                type="button" 
                                @click="openPetModal(@js($petModalData))" 
                                class="text-slate-500 hover:text-brand-600 font-medium cursor-pointer transition-colors flex items-center space-x-1"
                            >
                                <i class="fa-regular fa-id-card text-xs"></i>
                                <span>Details</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Upcoming & Active Consultations -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left 2 Cols: Upcoming Consultations -->
        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-xl font-bold text-slate-800 flex items-center">
                <i class="fa-solid fa-calendar-day text-brand-600 mr-2.5"></i> Upcoming Consultations
            </h2>

            @if($upcomingConsultations->isEmpty())
                <div class="bg-white rounded-2xl border border-slate-200/80 p-8 text-center text-slate-500">
                    <p class="text-sm font-medium">You have no upcoming consultations scheduled.</p>
                    <a href="{{ route('client.vets.search') }}" class="inline-flex items-center space-x-2 text-brand-600 font-semibold text-xs mt-2 hover:underline">
                        <span>Search available veterinarians →</span>
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($upcomingConsultations as $consult)
                        <div class="bg-white rounded-2xl border border-brand-200 p-6 shadow-sm hover:shadow-md transition-all flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 rounded-2xl bg-teal-100 text-brand-700 flex items-center justify-center text-xl shrink-0 font-bold">
                                    <i class="fa-solid {{ $consult->type === 'video' ? 'fa-video' : 'fa-comments' }}"></i>
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs uppercase font-extrabold px-2.5 py-0.5 rounded-full {{ $consult->status === 'accepted' ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800' }}">
                                            {{ ucfirst($consult->status) }}
                                        </span>
                                        <span class="text-xs text-slate-400">#{{ $consult->consultation_number }}</span>
                                    </div>
                                    <h3 class="font-bold text-slate-800 text-lg mt-1">{{ $consult->vet->name }}</h3>
                                    <p class="text-xs text-slate-500">
                                        For: <strong class="text-slate-700">{{ $consult->all_pets->pluck('name')->join(', ') }}</strong> • Scheduled: 
                                        <strong class="text-slate-800">{{ $consult->scheduled_at->format('M d, Y @ g:i A') }}</strong>
                                    </p>
                                    <p class="text-xs text-slate-600 mt-2 bg-slate-50 p-2 rounded-lg border border-slate-100 italic">
                                        "{{ Str::limit($consult->reason, 100) }}"
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row items-center gap-2 shrink-0">
                                @if($consult->status === 'accepted' || $consult->status === 'in_progress')
                                    @if($consult->type === 'video')
                                        <a href="{{ route('consultation.video', $consult) }}" class="w-full sm:w-auto bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs px-4 py-2.5 rounded-xl shadow-md shadow-brand-600/30 transition-all flex items-center justify-center space-x-2">
                                            <i class="fa-solid fa-video"></i>
                                            <span>Enter Video Call</span>
                                        </a>
                                    @endif
                                    <a href="{{ route('consultation.chat', $consult) }}" class="w-full sm:w-auto bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs px-4 py-2.5 rounded-xl transition-all flex items-center justify-center space-x-2">
                                        <i class="fa-solid fa-comments"></i>
                                        <span>Live Chat</span>
                                    </a>
                                @endif
                                <a href="{{ route('client.bookings.show', $consult) }}" class="text-xs text-slate-500 hover:text-slate-700 font-semibold px-3 py-2">Details</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Right Col: Recent Medical History -->
        <div class="space-y-4">
            <h2 class="text-xl font-bold text-slate-800 flex items-center">
                <i class="fa-solid fa-history text-brand-600 mr-2.5"></i> Recent Records
            </h2>

            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                @if($recentConsultations->isEmpty())
                    <p class="text-xs text-slate-500 text-center py-4">No past consultation records.</p>
                @else
                    @foreach($recentConsultations as $past)
                        <div class="border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800">{{ $past->pet->name }}</span>
                                <span class="text-slate-400">{{ $past->scheduled_at->format('M d, Y') }}</span>
                            </div>
                            <p class="text-xs text-slate-600 mt-0.5">Vet: {{ $past->vet->name }}</p>
                            @if($past->record)
                                <p class="text-[11px] text-emerald-700 font-medium mt-1">
                                    <i class="fa-solid fa-file-medical text-emerald-500 mr-1"></i> Medical Record Available
                                </p>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

    </div>

    <!-- Pet Details Modal -->
    <div 
        x-show="petModalOpen" 
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
            @click.outside="closePetModal()" 
            x-show="petModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-3"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-3"
            class="bg-white rounded-3xl shadow-2xl border border-slate-200/90 w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden relative text-slate-800"
        >
            <template x-if="selectedPet">
                <div class="flex flex-col h-full overflow-hidden">
                    
                    <!-- Modal Header -->
                    <div class="bg-slate-900 text-white p-5 sm:p-6 relative flex items-start justify-between border-b border-slate-800">
                        <div class="flex items-center space-x-4 pr-8">
                            <div class="w-16 h-16 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center text-brand-400 font-extrabold text-2xl shrink-0 overflow-hidden shadow-md">
                                <template x-if="selectedPet.photo_url">
                                    <img :src="selectedPet.photo_url" :alt="selectedPet.name" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!selectedPet.photo_url">
                                    <i class="fa-solid" :class="selectedPet.animal_icon"></i>
                                </template>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight" x-text="selectedPet.name"></h2>
                                    <span class="bg-brand-500/20 text-brand-300 border border-brand-500/30 text-[11px] font-bold px-2.5 py-0.5 rounded-full" x-text="selectedPet.sex"></span>
                                </div>
                                <p class="text-xs text-slate-300 mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span class="font-semibold text-brand-400" x-text="selectedPet.animal_type"></span>
                                    <span>•</span>
                                    <span x-text="selectedPet.breed_name"></span>
                                </p>
                                <div class="flex flex-wrap items-center gap-2 mt-2 text-xs">
                                    <span class="bg-slate-800 text-slate-300 border border-slate-700 px-2.5 py-0.5 rounded-lg text-[11px] flex items-center">
                                        <i class="fa-solid fa-cake-candles text-amber-400 mr-1.5 text-[10px]"></i>
                                        <span x-text="'Age: ' + selectedPet.age_text"></span>
                                        <template x-if="selectedPet.dob">
                                            <span class="text-slate-400 ml-1.5 text-[10px]" x-text="'(Born ' + selectedPet.dob + ')'"></span>
                                        </template>
                                    </span>
                                    <span class="bg-slate-800 text-slate-300 border border-slate-700 px-2.5 py-0.5 rounded-lg text-[11px] flex items-center">
                                        <i class="fa-solid fa-weight-scale text-sky-400 mr-1.5 text-[10px]"></i>
                                        <span x-text="'Weight: ' + selectedPet.weight"></span>
                                    </span>
                                    <span class="bg-slate-800 text-slate-300 border border-slate-700 px-2.5 py-0.5 rounded-lg text-[11px] flex items-center">
                                        <i class="fa-solid fa-palette text-pink-400 mr-1.5 text-[10px]"></i>
                                        <span x-text="'Color: ' + selectedPet.color"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Close Button -->
                        <button 
                            type="button" 
                            @click="closePetModal()" 
                            class="w-8 h-8 rounded-full bg-slate-800/80 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-colors shrink-0 cursor-pointer"
                            aria-label="Close Pet Details"
                        >
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <!-- Modal Scrollable Content -->
                    <div class="overflow-y-auto custom-scrollbar p-5 sm:p-6 space-y-5 flex-1 text-slate-700 text-xs">
                        
                        <!-- Quick Stats Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200/80 text-center">
                            <div class="p-2 bg-white rounded-xl border border-slate-100">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Species</span>
                                <span class="font-bold text-slate-800 text-xs mt-0.5 block truncate" x-text="selectedPet.animal_type"></span>
                            </div>
                            <div class="p-2 bg-white rounded-xl border border-slate-100">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Breed</span>
                                <span class="font-bold text-slate-800 text-xs mt-0.5 block truncate" x-text="selectedPet.breed_name"></span>
                            </div>
                            <div class="p-2 bg-white rounded-xl border border-slate-100">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Sex</span>
                                <span class="font-bold text-slate-800 text-xs mt-0.5 block truncate" x-text="selectedPet.sex"></span>
                            </div>
                            <div class="p-2 bg-white rounded-xl border border-slate-100">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Weight</span>
                                <span class="font-bold text-slate-800 text-xs mt-0.5 block truncate" x-text="selectedPet.weight"></span>
                            </div>
                        </div>

                        <!-- Medical Information Section -->
                        <div class="space-y-3">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center border-b border-slate-100 pb-2">
                                <i class="fa-solid fa-notes-medical text-brand-600 mr-2"></i> Medical Information & History
                            </h3>
                            
                            <div class="space-y-3">
                                <!-- Known Allergies -->
                                <div class="bg-rose-50/40 rounded-xl p-3.5 border border-rose-100/80">
                                    <div class="flex items-center space-x-1.5 text-rose-700 font-bold mb-1">
                                        <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                                        <span class="text-xs">Known Allergies</span>
                                    </div>
                                    <p class="text-xs text-slate-700 whitespace-pre-line pl-5" x-text="selectedPet.allergies"></p>
                                </div>

                                <!-- Existing Medical Conditions -->
                                <div class="bg-amber-50/40 rounded-xl p-3.5 border border-amber-100/80">
                                    <div class="flex items-center space-x-1.5 text-amber-700 font-bold mb-1">
                                        <i class="fa-solid fa-heart-pulse text-xs"></i>
                                        <span class="text-xs">Existing Medical Conditions</span>
                                    </div>
                                    <p class="text-xs text-slate-700 whitespace-pre-line pl-5" x-text="selectedPet.existing_conditions"></p>
                                </div>

                                <!-- Vaccination Record -->
                                <div class="bg-emerald-50/40 rounded-xl p-3.5 border border-emerald-100/80">
                                    <div class="flex items-center space-x-1.5 text-emerald-800 font-bold mb-1">
                                        <i class="fa-solid fa-shield-virus text-xs"></i>
                                        <span class="text-xs">Vaccination Record</span>
                                    </div>
                                    <p class="text-xs text-slate-700 whitespace-pre-line pl-5" x-text="selectedPet.vaccination_info"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Teleconsultations History Section -->
                        <div class="space-y-3 pt-2">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <h3 class="text-sm font-bold text-slate-800 flex items-center">
                                    <i class="fa-solid fa-clock-rotate-left text-brand-600 mr-2"></i> Teleconsultations History
                                </h3>
                                <span class="text-[11px] font-semibold text-slate-500" x-text="selectedPet.consultations ? '(' + selectedPet.consultations.length + ')' : ''"></span>
                            </div>

                            <div class="space-y-2.5">
                                <template x-if="selectedPet.consultations && selectedPet.consultations.length > 0">
                                    <template x-for="consult in selectedPet.consultations" :key="consult.id">
                                        <div class="bg-slate-50/70 p-3 rounded-xl border border-slate-100 flex items-center justify-between gap-3">
                                            <div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-bold text-slate-800 text-xs" x-text="consult.vet_name"></span>
                                                    <span 
                                                        class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full"
                                                        :class="{
                                                            'bg-emerald-100 text-emerald-800': consult.status_raw === 'completed',
                                                            'bg-sky-100 text-sky-800': consult.status_raw === 'accepted' || consult.status_raw === 'scheduled',
                                                            'bg-amber-100 text-amber-800': consult.status_raw === 'pending',
                                                            'bg-slate-100 text-slate-600': consult.status_raw !== 'completed' && consult.status_raw !== 'accepted' && consult.status_raw !== 'scheduled' && consult.status_raw !== 'pending'
                                                        }"
                                                        x-text="consult.status"
                                                    ></span>
                                                </div>
                                                <p class="text-slate-400 text-[11px] mt-0.5" x-text="consult.date"></p>
                                            </div>
                                            <template x-if="consult.has_record">
                                                <a 
                                                    :href="consult.record_url" 
                                                    class="text-brand-600 hover:text-brand-700 font-semibold text-xs shrink-0 flex items-center space-x-1"
                                                >
                                                    <span>View Record</span>
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                                </a>
                                            </template>
                                        </div>
                                    </template>
                                </template>
                                <template x-if="!selectedPet.consultations || selectedPet.consultations.length === 0">
                                    <div class="bg-slate-50 rounded-xl p-4 text-center text-slate-400 italic">
                                        No teleconsultations recorded yet for this pet.
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-slate-50 border-t border-slate-200/80 p-4 px-6 flex items-center justify-between gap-3">
                        <button 
                            type="button" 
                            @click="closePetModal()" 
                            class="bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 font-semibold text-xs py-2.5 px-4 rounded-xl transition-all cursor-pointer"
                        >
                            Close
                        </button>
                        <div class="flex items-center space-x-2">
                            <a 
                                :href="selectedPet.edit_url" 
                                class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-2.5 px-3.5 rounded-xl transition-all flex items-center space-x-1.5"
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                                <span>Edit Pet</span>
                            </a>
                            <a 
                                :href="selectedPet.book_url" 
                                class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-md shadow-brand-600/20 transition-all flex items-center space-x-1.5"
                            >
                                <i class="fa-solid fa-calendar-check"></i>
                                <span>Book Vet</span>
                            </a>
                        </div>
                    </div>

                </div>
            </template>
        </div>
    </div>

</div>
@endsection
