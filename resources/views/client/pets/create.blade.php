@extends('layouts.app')

@section('title', 'Register New Pet')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
        <div class="flex items-center space-x-3 mb-6">
            <a href="{{ route('client.pets.index') }}" class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center hover:bg-slate-200">
                <i class="fa-solid fa-arrow-left text-xs"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Register New Pet</h1>
                <p class="text-xs text-slate-500">Provide details about your pet to share with veterinarians</p>
            </div>
        </div>

        <form method="POST" action="{{ route('client.pets.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Pet Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="e.g. Max"
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                </div>

                <div>
                    <label for="animal_type_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Animal Category *</label>
                    <select name="animal_type_id" id="animal_type_id" required class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                        <option value="">Select Category</option>
                        @foreach($animalTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="custom_breed" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Breed / Mix</label>
                    <input type="text" name="custom_breed" id="custom_breed" value="{{ old('custom_breed') }}" placeholder="e.g. Golden Retriever / Mixed"
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                </div>

                <div>
                    <label for="sex" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Sex *</label>
                    <select name="sex" id="sex" required class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="age_text" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Age (e.g. 2 years)</label>
                    <input type="text" name="age_text" id="age_text" value="{{ old('age_text') }}" placeholder="e.g. 3 years old"
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                </div>

                <div>
                    <label for="weight" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Weight (e.g. 5.5 kg)</label>
                    <input type="text" name="weight" id="weight" value="{{ old('weight') }}" placeholder="e.g. 6 kg"
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                </div>

                <div>
                    <label for="color" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Color / Marking</label>
                    <input type="text" name="color" id="color" value="{{ old('color') }}" placeholder="e.g. Brown & White"
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                </div>
            </div>

            <div>
                <label for="photo" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Pet Photo (Optional)</label>
                <input type="file" name="photo" id="photo" accept="image/*"
                    class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 border border-slate-200 rounded-xl">
            </div>

            <div>
                <label for="existing_conditions" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Existing Medical Conditions</label>
                <textarea name="existing_conditions" id="existing_conditions" rows="2" placeholder="e.g. Mild arthritis, sensitive stomach"
                    class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2 shadow-sm"></textarea>
            </div>

            <div>
                <label for="allergies" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Known Allergies</label>
                <textarea name="allergies" id="allergies" rows="2" placeholder="e.g. Chicken protein allergy, flea hypersensitivity"
                    class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2 shadow-sm"></textarea>
            </div>

            <div>
                <label for="vaccination_info" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Vaccination History</label>
                <textarea name="vaccination_info" id="vaccination_info" rows="2" placeholder="e.g. 5-in-1 updated June 2026, Anti-Rabies updated July 2026"
                    class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2 shadow-sm"></textarea>
            </div>

            <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-semibold py-3 rounded-xl shadow-md shadow-brand-600/30 transition-all text-sm mt-4">
                Save Pet Profile
            </button>
        </form>
    </div>
</div>
@endsection
