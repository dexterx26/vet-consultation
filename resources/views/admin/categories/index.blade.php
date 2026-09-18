@extends('layouts.app')

@section('title', 'Manage Animal Types & Breeds')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Pet Categories & Breed Configurator</h1>
        <p class="text-xs text-slate-500 mt-1">Configure supported animal species and breeds for client pet registration</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Add Animal Type Form -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
            <h2 class="text-lg font-bold text-slate-800">Add Animal Species</h2>
            <form method="POST" action="{{ route('admin.categories.type.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Species Name</label>
                    <input type="text" name="name" required placeholder="e.g. Ferret" class="w-full rounded-xl border-slate-200 text-xs py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">FontAwesome Icon</label>
                    <input type="text" name="icon" value="fa-paw" required class="w-full rounded-xl border-slate-200 text-xs py-2">
                </div>
                <button type="submit" class="w-full bg-brand-600 text-white font-semibold text-xs py-2.5 rounded-xl">Add Animal Type</button>
            </form>

            <hr class="border-slate-100">

            <h2 class="text-lg font-bold text-slate-800">Add Breed</h2>
            <form method="POST" action="{{ route('admin.categories.breed.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Animal Category</label>
                    <select name="animal_type_id" required class="w-full rounded-xl border-slate-200 text-xs py-2">
                        @foreach($animalTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Breed Name</label>
                    <input type="text" name="name" required placeholder="e.g. Beagle" class="w-full rounded-xl border-slate-200 text-xs py-2">
                </div>
                <button type="submit" class="w-full bg-slate-800 text-white font-semibold text-xs py-2.5 rounded-xl">Add Breed</button>
            </form>
        </div>

        <!-- Species & Breeds Display -->
        <div class="md:col-span-2 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($animalTypes as $type)
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-lg border border-brand-100">
                                    <i class="fa-solid {{ $type->icon }}"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-base">{{ $type->name }}</h3>
                                    <span class="text-[10px] text-slate-400">{{ $type->pets_count }} Registered Pets</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1 text-xs">
                            <span class="font-semibold text-slate-700 block text-[11px] uppercase tracking-wider">Breeds:</span>
                            <div class="flex flex-wrap gap-1">
                                @forelse($type->breeds as $b)
                                    <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-[11px] flex items-center space-x-1">
                                        <span>{{ $b->name }}</span>
                                    </span>
                                @empty
                                    <span class="text-slate-400 italic text-[11px]">No specific breeds added.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</div>
@endsection
