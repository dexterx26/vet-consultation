@extends('layouts.app')

@section('title', 'My Pets')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">My Pets</h1>
            <p class="text-slate-500 text-xs mt-1">Manage your pet profiles, medical history, and vaccination records</p>
        </div>
        <a href="{{ route('client.pets.create') }}" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs px-4 py-2.5 rounded-xl shadow-md shadow-brand-600/30 transition-all flex items-center space-x-2">
            <i class="fa-solid fa-plus"></i>
            <span>Register New Pet</span>
        </a>
    </div>

    @if($pets->isEmpty())
        <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-500">
            <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-paw text-3xl"></i>
            </div>
            <h3 class="font-bold text-slate-700 text-lg">No Pets Added Yet</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Create a profile for your pet so veterinarians can inspect medical history during consultations.</p>
            <a href="{{ route('client.pets.create') }}" class="inline-flex items-center space-x-2 bg-brand-600 text-white text-xs font-semibold px-5 py-2.5 rounded-xl mt-6 shadow-md shadow-brand-600/30">
                <i class="fa-solid fa-plus"></i> <span>Add Pet Profile</span>
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($pets as $pet)
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between">
                    <div class="p-6">
                        <div class="flex items-start space-x-4">
                            <div class="w-16 h-16 rounded-2xl bg-brand-50 border border-brand-100 flex items-center justify-center text-brand-600 font-bold text-2xl shrink-0 overflow-hidden">
                                @if($pet->photo)
                                    <img src="{{ asset('storage/' . $pet->photo) }}" class="w-full h-full object-cover">
                                @else
                                    <i class="fa-solid {{ $pet->animalType ? $pet->animalType->icon : 'fa-paw' }}"></i>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-bold text-slate-800 text-lg leading-tight">{{ $pet->name }}</h3>
                                <p class="text-xs text-brand-600 font-medium">{{ $pet->animalType->name ?? 'Pet' }} • {{ $pet->breed_name }}</p>
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    <span class="text-[10px] font-semibold bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{{ $pet->sex }}</span>
                                    <span class="text-[10px] font-semibold bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{{ $pet->age_text . ' year/s old' ?: 'Age N/A' }}</span>
                                    @if($pet->weight)<span class="text-[10px] font-semibold bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{{ $pet->weight }} kg</span>@endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-xs text-slate-600">
                            @if($pet->allergies)
                                <p><strong class="text-rose-600">Allergies:</strong> {{ $pet->allergies }}</p>
                            @endif
                            @if($pet->existing_conditions)
                                <p><strong class="text-amber-600">Conditions:</strong> {{ $pet->existing_conditions }}</p>
                            @endif
                            @if($pet->vaccination_info)
                                <p><strong class="text-emerald-700">Vaccines:</strong> {{ Str::limit($pet->vaccination_info, 80) }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <a href="{{ route('client.pets.show', $pet) }}" class="text-slate-600 font-semibold hover:text-slate-900">Full Details & Medical History</a>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('client.pets.edit', $pet) }}" class="text-brand-600 hover:text-brand-800 font-medium"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
