@extends('layouts.app')

@section('title', 'Schedule & Consultation Fee')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200/80 p-8 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-800">Veterinarian Profile & Settings</h1>
        <p class="text-xs text-slate-500 mt-1">Set your consultation fee, clinic address, and weekly availability hours</p>

        <form method="POST" action="{{ route('vet.schedule.profile') }}" class="mt-6 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Base Consultation Fee (₱) *</label>
                    <input type="number" step="0.01" name="consultation_fee" value="{{ old('consultation_fee', $profile->consultation_fee) }}" required
                        class="w-full rounded-xl border-slate-200 text-sm py-2.5 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Clinic / Hospital Name</label>
                    <input type="text" name="clinic_name" value="{{ old('clinic_name', $profile->clinic_name) }}"
                        class="w-full rounded-xl border-slate-200 text-sm py-2.5 focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>

            <!-- Additional Pet Pricing & Time Settings -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-brand-50/50 p-4 rounded-xl border border-brand-100">
                <div>
                    <label class="block text-xs font-bold text-brand-900 uppercase tracking-wider mb-1">
                        Additional Pet Extra Fee (₱) *
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold text-xs">₱</span>
                        <input type="number" step="0.01" name="additional_pet_fee" value="{{ old('additional_pet_fee', $profile->effective_additional_pet_fee) }}" required min="0"
                            class="w-full pl-7 rounded-xl border-slate-200 text-sm py-2.5 focus:ring-brand-500 focus:border-brand-500 bg-white">
                    </div>
                    <span class="text-[11px] text-slate-500 mt-1 block">Fee added for each additional pet included in a consultation.</span>
                </div>
                <div>
                    <label class="block text-xs font-bold text-brand-900 uppercase tracking-wider mb-1">
                        Additional Pet Extra Time (Minutes) *
                    </label>
                    <div class="relative">
                        <input type="number" name="additional_pet_duration" value="{{ old('additional_pet_duration', $profile->effective_additional_pet_duration) }}" required min="1" max="120"
                            class="w-full pr-12 rounded-xl border-slate-200 text-sm py-2.5 focus:ring-brand-500 focus:border-brand-500 bg-white">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 font-bold text-xs">mins</span>
                    </div>
                    <span class="text-[11px] text-slate-500 mt-1 block">Extra duration added to the video call / chat session for each extra pet.</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Languages Spoken</label>
                    <input type="text" name="languages" value="{{ old('languages', $profile->languages) }}" placeholder="e.g. English, Tagalog"
                        class="w-full rounded-xl border-slate-200 text-sm py-2.5 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Clinic Street Address</label>
                    <input type="text" name="clinic_address" value="{{ old('clinic_address', $profile->clinic_address) }}"
                        class="w-full rounded-xl border-slate-200 text-sm py-2.5 focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Areas of Expertise</label>
                <input type="text" name="expertise" value="{{ old('expertise', $profile->expertise) }}" required
                    class="w-full rounded-xl border-slate-200 text-sm py-2.5 focus:ring-brand-500 focus:border-brand-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Professional Bio</label>
                <textarea name="bio" rows="3" class="w-full rounded-xl border-slate-200 text-sm py-2 focus:ring-brand-500 focus:border-brand-500">{{ old('bio', $profile->bio) }}</textarea>
            </div>

            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs px-5 py-2.5 rounded-xl shadow-sm">
                Save Profile & Fee Settings
            </button>
        </form>
    </div>

    <!-- Weekly Availability Slots -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-8 shadow-sm space-y-6">
        <h2 class="text-xl font-bold text-slate-800">Weekly Availability Schedule</h2>

        <form method="POST" action="{{ route('vet.schedule.availability') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end bg-slate-50 p-4 rounded-2xl border border-slate-100">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Day of Week</label>
                <select name="day_of_week" required class="w-full rounded-xl border-slate-200 text-xs py-2">
                    <option value="1">Monday</option>
                    <option value="2">Tuesday</option>
                    <option value="3">Wednesday</option>
                    <option value="4">Thursday</option>
                    <option value="5">Friday</option>
                    <option value="6">Saturday</option>
                    <option value="0">Sunday</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Start Time</label>
                <input type="time" name="start_time" value="09:00" required class="w-full rounded-xl border-slate-200 text-xs py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">End Time</label>
                <input type="time" name="end_time" value="17:00" required class="w-full rounded-xl border-slate-200 text-xs py-2">
            </div>
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs py-2.5 px-4 rounded-xl">
                Add Slot
            </button>
        </form>

        <div class="space-y-2">
            @forelse($availabilities as $slot)
                <div class="flex items-center justify-between p-3.5 bg-white border border-slate-200 rounded-xl text-xs">
                    <span class="font-bold text-slate-800 w-28">{{ $slot->day_name }}</span>
                    <span class="text-slate-600 font-mono">{{ date('h:i A', strtotime($slot->start_time)) }} — {{ date('h:i A', strtotime($slot->end_time)) }}</span>
                    <form method="POST" action="{{ route('vet.schedule.availability.delete', $slot) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-rose-500 hover:text-rose-700 font-semibold"><i class="fa-solid fa-trash"></i> Remove</button>
                    </form>
                </div>
            @empty
                <p class="text-xs text-slate-400 italic text-center py-4">No recurring schedule slots defined yet.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
