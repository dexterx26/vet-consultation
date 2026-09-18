@extends('layouts.app')

@section('title', 'Pet Details — ' . $pet->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Pet Profile Banner -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
        <div class="flex items-center space-x-5">
            <div class="w-20 h-20 rounded-2xl bg-brand-50 border border-brand-100 flex items-center justify-center text-brand-600 font-bold text-3xl shrink-0 overflow-hidden shadow-sm">
                @if($pet->photo)
                    <img src="{{ asset('storage/' . $pet->photo) }}" class="w-full h-full object-cover">
                @else
                    <i class="fa-solid {{ $pet->animalType ? $pet->animalType->icon : 'fa-paw' }}"></i>
                @endif
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-2xl font-bold text-slate-800">{{ $pet->name }}</h1>
                    <span class="bg-slate-100 text-slate-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">{{ $pet->sex }}</span>
                </div>
                <p class="text-xs text-brand-600 font-semibold mt-1">{{ $pet->animalType->name ?? 'Pet' }} • {{ $pet->breed_name }}</p>
                <p class="text-xs text-slate-500 mt-0.5">Age: {{ $pet->age_text ?: 'N/A' }} • Weight: {{ $pet->weight ?: 'N/A' }} • Color: {{ $pet->color ?: 'N/A' }}</p>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('client.vets.search', ['animal_type' => $pet->animalType->name ?? '']) }}" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs px-4 py-2.5 rounded-xl shadow-md shadow-brand-600/20 transition-all flex items-center space-x-1.5">
                <i class="fa-solid fa-user-doctor"></i>
                <span>Find Vet for {{ $pet->name }}</span>
            </a>
            <a href="{{ route('client.pets.edit', $pet) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs px-3.5 py-2.5 rounded-xl transition-all">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
        </div>
    </div>

    <!-- Medical Details & Consultation History -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols: Medical Overview -->
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">Medical Information & History</h2>
                
                <div class="space-y-3 text-xs text-slate-700">
                    <div>
                        <strong class="text-rose-600 block mb-0.5">Known Allergies:</strong>
                        <p class="bg-slate-50 p-3 rounded-xl border border-slate-100">{{ $pet->allergies ?: 'No allergies recorded.' }}</p>
                    </div>

                    <div>
                        <strong class="text-amber-600 block mb-0.5">Existing Medical Conditions:</strong>
                        <p class="bg-slate-50 p-3 rounded-xl border border-slate-100">{{ $pet->existing_conditions ?: 'No chronic conditions recorded.' }}</p>
                    </div>

                    <div>
                        <strong class="text-emerald-700 block mb-0.5">Vaccination Record:</strong>
                        <p class="bg-slate-50 p-3 rounded-xl border border-slate-100">{{ $pet->vaccination_info ?: 'No vaccination history added.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Col: Past Consultations for this Pet -->
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">Teleconsultations History</h2>
                
                @forelse($pet->consultations as $consult)
                    <div class="border-b border-slate-100 pb-3 last:border-0 last:pb-0 text-xs">
                        <div class="flex items-center justify-between font-bold text-slate-800">
                            <span>{{ $consult->vet->name ?? 'Vet' }}</span>
                            <span class="text-[10px] uppercase px-2 py-0.5 rounded bg-slate-100 text-slate-700">{{ $consult->status }}</span>
                        </div>
                        <p class="text-slate-400 text-[11px] mt-0.5">{{ $consult->scheduled_at->format('M d, Y') }}</p>
                        @if($consult->record)
                            <a href="{{ route('client.bookings.show', $consult) }}" class="text-brand-600 font-semibold text-[11px] mt-1 inline-block hover:underline">
                                View Prescription & Record →
                            </a>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic">No past consultations for {{ $pet->name }}.</p>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
