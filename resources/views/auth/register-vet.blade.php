@extends('layouts.app')

@section('title', 'Veterinarian Registration')

@section('content')
<div class="max-w-2xl mx-auto my-8">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-teal-50 text-teal-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-teal-100 shadow-sm">
                <i class="fa-solid fa-user-doctor text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Veterinarian Professional Registration</h1>
            <p class="text-slate-500 text-sm mt-1">Join our network of licensed teleconsultation veterinarians</p>
        </div>

        <form method="POST" action="{{ route('register.vet') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-xl p-3.5 text-xs flex items-start space-x-3 mb-4">
                <i class="fa-solid fa-circle-info text-amber-600 text-base mt-0.5"></i>
                <p>New veterinarian applications undergo document verification by system administrators before receiving live consultation requests.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Full Name (with credentials)</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="Dr. Juan Dela Cruz, DVM"
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 px-3.5 shadow-sm">
                    @error('name') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="license_number" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">PRC License Number</label>
                    <input type="text" name="license_number" id="license_number" value="{{ old('license_number') }}" required placeholder="PRC-VET-12345"
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 px-3.5 shadow-sm">
                    @error('license_number') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required placeholder="doctor@clinic.com"
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 px-3.5 shadow-sm">
                    @error('email') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Contact Number</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required placeholder="09181234567"
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 px-3.5 shadow-sm">
                    @error('phone') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="years_experience" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Years of Experience</label>
                    <input type="number" name="years_experience" id="years_experience" value="{{ old('years_experience', 5) }}" min="0" required
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 px-3.5 shadow-sm">
                </div>
                <div>
                    <label for="consultation_fee" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Consultation Fee (₱)</label>
                    <input type="number" step="0.01" name="consultation_fee" id="consultation_fee" value="{{ old('consultation_fee', '500.00') }}" required
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 px-3.5 shadow-sm">
                </div>
                <div>
                    <label for="city" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">City / Municipality</label>
                    <input type="text" name="city" id="city" value="{{ old('city', 'Quezon City') }}" required
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 px-3.5 shadow-sm">
                </div>
            </div>

            <div>
                <label for="clinic_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Clinic / Hospital Name (Optional)</label>
                <input type="text" name="clinic_name" id="clinic_name" value="{{ old('clinic_name') }}" placeholder="e.g. Companion Animal Hospital"
                    class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 px-3.5 shadow-sm">
            </div>

            <div>
                <label for="expertise" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Areas of Specialization / Expertise</label>
                <input type="text" name="expertise" id="expertise" value="{{ old('expertise', 'Small Animal Internal Medicine, Surgical Care') }}" required
                    class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 px-3.5 shadow-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Animal Types Handled</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs font-medium text-slate-700">
                    <label class="flex items-center space-x-2 bg-slate-50 p-2 rounded-lg border border-slate-200"><input type="checkbox" name="animals_handled[]" value="Dog" checked class="text-brand-600 rounded"> <span>Dogs</span></label>
                    <label class="flex items-center space-x-2 bg-slate-50 p-2 rounded-lg border border-slate-200"><input type="checkbox" name="animals_handled[]" value="Cat" checked class="text-brand-600 rounded"> <span>Cats</span></label>
                    <label class="flex items-center space-x-2 bg-slate-50 p-2 rounded-lg border border-slate-200"><input type="checkbox" name="animals_handled[]" value="Bird" class="text-brand-600 rounded"> <span>Birds</span></label>
                    <label class="flex items-center space-x-2 bg-slate-50 p-2 rounded-lg border border-slate-200"><input type="checkbox" name="animals_handled[]" value="Rabbit" class="text-brand-600 rounded"> <span>Rabbits</span></label>
                </div>
            </div>

            <div>
                <label for="document" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Upload Professional License / ID Document (PDF or Image)</label>
                <input type="file" name="document" id="document" required accept=".pdf,.jpg,.jpeg,.png"
                    class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 border border-slate-200 rounded-xl">
                @error('document') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <input type="hidden" name="province" value="Metro Manila">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password</label>
                    <input type="password" name="password" id="password" required
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 px-3.5 shadow-sm">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 px-3.5 shadow-sm">
                </div>
            </div>

            <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-semibold py-3 rounded-xl shadow-md shadow-brand-600/30 transition-all text-sm mt-4">
                Submit Veterinarian Application
            </button>
        </form>
    </div>
</div>
@endsection
