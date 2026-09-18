@extends('layouts.app')

@section('title', 'Client Dashboard')

@section('content')
<div class="space-y-8">
    
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
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-all group relative">
                        <div class="flex items-center space-x-4">
                            <div class="w-14 h-14 rounded-2xl bg-brand-50 border border-brand-100 flex items-center justify-center text-brand-600 font-bold text-xl shrink-0 overflow-hidden">
                                @if($pet->photo)
                                    <img src="{{ asset('storage/' . $pet->photo) }}" class="w-full h-full object-cover">
                                @else
                                    <i class="fa-solid {{ $pet->animalType ? $pet->animalType->icon : 'fa-paw' }}"></i>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-bold text-slate-800 text-base group-hover:text-brand-600 transition-colors truncate">{{ $pet->name }}</h3>
                                <p class="text-xs text-slate-500 capitalize">{{ $pet->animalType->name ?? 'Pet' }} • {{ $pet->breed_name }}</p>
                                <span class="inline-block text-[10px] bg-slate-100 text-slate-600 font-semibold px-2 py-0.5 rounded mt-1.5">{{ $pet->sex }} • {{ $pet->age_text ?: 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <a href="{{ route('client.vets.search', ['animal_type' => $pet->animalType->name ?? '']) }}" class="text-brand-600 font-semibold hover:underline flex items-center space-x-1">
                                <span>Book Vet</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                            <a href="{{ route('client.pets.show', $pet) }}" class="text-slate-400 hover:text-slate-600 font-medium">Details</a>
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
                                        For: <strong class="text-slate-700">{{ $consult->pet->name }}</strong> • Scheduled: 
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

</div>
@endsection
