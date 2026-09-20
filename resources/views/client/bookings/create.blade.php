@extends('layouts.app')

@section('title', 'Book Consultation — Dr. ' . $vet->name)

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="bookingCalendarComponent({{ $vet->id }}, {{ $baseCreditsCost }}, {{ $addCreditsCost }}, {{ $clientCredits }}, {{ $baseFee }}, {{ $addFee }}, {{ $baseDuration }}, {{ $addDuration }}, {{ $preselectedPetId }})">

    <!-- Back button & Page Title -->
    <div class="flex items-center space-x-3">
        <a href="{{ route('client.vets.show', ['vet' => $vet->id, 'pet_id' => $preselectedPetId]) }}" class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-50 transition-all shadow-sm">
            <i class="fa-solid fa-arrow-left text-xs"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Book Consultation</h1>
            <p class="text-xs text-slate-500">Schedule a teleconsultation with Dr. {{ $vet->name }}</p>
        </div>
    </div>

    <!-- Credits & Price Information Card -->
    <div class="bg-gradient-to-r from-navy-800 to-slate-900 rounded-3xl p-6 text-white shadow-xl border border-slate-700/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start space-x-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-400/20 border border-amber-400/30 text-amber-400 flex items-center justify-center text-2xl shadow-inner shrink-0 mt-1">
                <i class="fa-solid fa-coins"></i>
            </div>
            <div>
                <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Consultation Cost & Duration</span>
                <div class="flex items-baseline space-x-2">
                    <span class="text-2xl font-black text-amber-400 font-mono" x-text="totalCreditsCost"></span>
                    <span class="text-xs text-slate-300 font-semibold">Credits</span>
                    <span class="text-slate-400 text-xs">•</span>
                    <span class="text-sm font-bold text-emerald-400 font-mono">₱<span x-text="totalFee.toFixed(2)"></span></span>
                </div>
                <div class="flex items-center space-x-2 text-[11px] text-slate-300 mt-1">
                    <span class="bg-white/10 px-2 py-0.5 rounded text-amber-300 font-medium">
                        <i class="fa-regular fa-clock mr-1"></i> <span x-text="totalDuration"></span> mins total time
                    </span>
                    <span class="text-slate-400">•</span>
                    <span><span x-text="extraPetCount + 1"></span> pet(s) included</span>
                </div>
            </div>
        </div>

        <div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-4 sm:text-right flex sm:flex-col justify-between items-center sm:items-end shrink-0">
            <span class="text-[11px] text-slate-400 uppercase font-semibold">Your Available Balance</span>
            <span class="text-xl font-black font-mono" :class="hasSufficientCredits ? 'text-emerald-400' : 'text-rose-400'">
                {{ number_format($clientCredits) }} Credits
            </span>
            <template x-if="!hasSufficientCredits">
                <span class="text-[10px] text-rose-400 font-bold mt-1 bg-rose-500/10 px-2 py-0.5 rounded border border-rose-500/20">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> Needs Top Up
                </span>
            </template>
            <template x-if="hasSufficientCredits">
                <span class="text-[10px] text-emerald-400 font-bold mt-1 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">
                    <i class="fa-solid fa-circle-check mr-1"></i> Sufficient Credits
                </span>
            </template>
        </div>
    </div>

    <!-- Insufficient Credits Alert -->
    <div x-show="!hasSufficientCredits" class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-4 text-xs flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-lg shrink-0"></i>
            <span>You do not have enough credits for this booking (Required: <strong x-text="totalCreditsCost"></strong> credits, Current: <strong>{{ $clientCredits }} credits</strong>). Please ask an administrator to top up your account.</span>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 p-8 space-y-6">

        <form method="POST" action="{{ route('client.bookings.store') }}" enctype="multipart/form-data" class="space-y-6" @submit="validateSubmission($event)">
            @csrf
            <input type="hidden" name="vet_id" value="{{ $vet->id }}">
            <input type="hidden" name="scheduled_date" :value="selectedDate">
            <input type="hidden" name="scheduled_time" :value="selectedTime">

            <!-- Step 1: Select Primary Pet & Multi-Pet Additions -->
            <div class="space-y-4">
                <div>
                    <label for="pet_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        1. Primary Pet for Consultation *
                    </label>
                    <select name="pet_id" id="pet_id" x-model="primaryPetId" @change="onPrimaryPetChange()" required
                            class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-3 shadow-sm font-medium text-slate-800">
                        @foreach($pets as $pet)
                            @if($pet->is_handled)
                                <option value="{{ $pet->id }}" {{ $preselectedPetId == $pet->id ? 'selected' : '' }}>
                                    🐾 {{ $pet->name }} ({{ $pet->animalType->name ?? 'Pet' }} • {{ $pet->breed_name }})
                                </option>
                            @else
                                <option value="{{ $pet->id }}" disabled class="text-slate-400 bg-slate-50">
                                    ⚠️ {{ $pet->name }} ({{ $pet->animalType->name ?? 'Pet' }}) — [Dr. {{ $vet->name }} does not handle {{ $pet->animalType->name ?? 'this animal' }}]
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1 flex items-center">
                        <i class="fa-solid fa-stethoscope text-brand-600 mr-1.5"></i>
                        <span>Doctor handles: <strong class="text-slate-700">{{ implode(', ', $animalsHandled) }}</strong></span>
                    </p>
                </div>

                <!-- Multi-Pet Addition Section -->
                @if($pets->count() > 1)
                    <div class="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center">
                                    <i class="fa-solid fa-plus-circle text-brand-600 mr-1.5"></i> Add Another Pet to this Consultation
                                </h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    If the doctor handles dogs and cats, you can add another pet in that different category. Each succeeding pet adds <strong>+₱{{ number_format($addFee, 2) }}</strong>, <strong>+{{ $addDuration }} mins</strong> duration, and <strong>+{{ $addCreditsCost }} credits</strong>.
                                </p>
                            </div>
                            <span class="text-[10px] font-bold bg-brand-100 text-brand-800 px-2 py-0.5 rounded-full" x-text="extraPetCount + ' extra added'"></span>
                        </div>

                        <div class="space-y-2 pt-1">
                            @foreach($pets as $otherPet)
                                <div x-show="primaryPetId != {{ $otherPet->id }}" class="transition-all">
                                    @if($otherPet->is_handled)
                                        <label class="flex items-center justify-between p-3 bg-white rounded-xl border border-slate-200 hover:border-brand-400 cursor-pointer transition-all shadow-2xs has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/30">
                                            <div class="flex items-center space-x-3">
                                                <input type="checkbox" name="additional_pet_ids[]" value="{{ $otherPet->id }}"
                                                       x-model="selectedAdditionalPetIds"
                                                       class="w-4 h-4 text-brand-600 rounded border-slate-300 focus:ring-brand-500">
                                                <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center font-bold text-xs shrink-0">
                                                    <i class="fa-solid {{ $otherPet->animalType ? $otherPet->animalType->icon : 'fa-paw' }}"></i>
                                                </div>
                                                <div>
                                                    <span class="font-bold text-slate-800 text-xs block">{{ $otherPet->name }}</span>
                                                    <span class="text-[10px] text-slate-500">{{ $otherPet->animalType->name ?? 'Pet' }} • {{ $otherPet->breed_name }} ({{ $otherPet->sex }})</span>
                                                </div>
                                            </div>
                                            <div class="text-right text-[11px]">
                                                <span class="font-extrabold text-emerald-700 block">+₱{{ number_format($addFee, 2) }}</span>
                                                <span class="text-[10px] text-slate-400">+{{ $addDuration }} mins • +{{ $addCreditsCost }} pts</span>
                                            </div>
                                        </label>
                                    @else
                                        <div class="flex items-center justify-between p-2.5 bg-slate-100/60 rounded-xl border border-slate-200/60 text-slate-400 opacity-70 cursor-not-allowed text-xs">
                                            <div class="flex items-center space-x-3">
                                                <input type="checkbox" disabled class="w-4 h-4 text-slate-300 rounded border-slate-300 cursor-not-allowed">
                                                <div class="w-8 h-8 rounded-lg bg-slate-200 text-slate-400 flex items-center justify-center font-bold text-xs shrink-0">
                                                    <i class="fa-solid fa-ban"></i>
                                                </div>
                                                <div>
                                                    <span class="font-semibold text-slate-500 text-xs block">{{ $otherPet->name }} ({{ $otherPet->animalType->name ?? 'Pet' }})</span>
                                                    <span class="text-[10px] text-slate-400">Doctor does not handle {{ $otherPet->animalType->name ?? 'this category' }}</span>
                                                </div>
                                            </div>
                                            <span class="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded font-medium">Not Handled</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Dynamic Multi-Pet Summary Banner -->
                        <div x-show="extraPetCount > 0" class="bg-brand-50 border border-brand-200 rounded-xl p-3 text-xs text-brand-900 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-sparkles text-brand-600 text-sm"></i>
                                <span>Multi-Pet Consultation Active: <strong x-text="extraPetCount + 1"></strong> pets will be examined in a combined <strong x-text="totalDuration"></strong>-minute session.</span>
                            </div>
                            <span class="font-extrabold text-brand-800" x-text="'₱' + totalFee.toFixed(2)"></span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Step 2: Consultation Mode -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    2. Consultation Mode *
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="relative flex items-center p-4 bg-white border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-brand-500 transition-all has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/40 shadow-sm">
                        <input type="radio" name="type" value="video" checked class="sr-only">
                        <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg mr-3">
                            <i class="fa-solid fa-video"></i>
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 text-sm">Live Video Call</div>
                            <p class="text-[11px] text-slate-500">1-on-1 direct WebRTC video consultation</p>
                        </div>
                    </label>

                    <label class="relative flex items-center p-4 bg-white border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-brand-500 transition-all has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/40 shadow-sm">
                        <input type="radio" name="type" value="chat" class="sr-only">
                        <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg mr-3">
                            <i class="fa-solid fa-comments"></i>
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 text-sm">Live Text Chat</div>
                            <p class="text-[11px] text-slate-500">Real-time messaging & medical attachment sharing</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Step 3: Interactive Calendar & Real-Time Availability View -->
            <div class="border-t border-slate-100 pt-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                            3. Select Consultation Date & Time Slot *
                        </label>
                        <p class="text-xs text-slate-500">Real-time schedule. Slots are locked on a first-come, first-served basis.</p>
                    </div>

                    <div class="flex items-center space-x-2 text-xs">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                            Live Real-Time Sync
                        </span>
                        <button type="button" @click="fetchSlots()" class="text-slate-400 hover:text-brand-600 p-1 transition-colors" title="Refresh available slots">
                            <i class="fa-solid fa-rotate text-xs" :class="loadingSlots ? 'animate-spin' : ''"></i>
                        </button>
                    </div>
                </div>

                <!-- Date Picker & Quick Days Strip -->
                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 space-y-4">
                    
                    <!-- Date Input -->
                    <div class="flex flex-col sm:flex-row items-center gap-3">
                        <div class="w-full sm:w-auto">
                            <span class="text-xs font-semibold text-slate-600 block mb-1">Pick a Date:</span>
                            <input type="date" x-model="selectedDate" @change="onDateChange()"
                                   min="{{ date('Y-m-d') }}"
                                   class="rounded-xl border-slate-300 text-xs font-semibold text-slate-800 py-2 px-3 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>

                        <!-- 7 Days Quick Select Tabs -->
                        <div class="w-full overflow-x-auto flex items-center space-x-2 pb-1 pt-4 sm:pt-0 custom-scrollbar">
                            <template x-for="day in upcomingDays" :key="day.date">
                                <button type="button" @click="selectedDate = day.date; onDateChange()"
                                        :class="selectedDate === day.date ? 'bg-brand-600 text-white border-brand-600 shadow-md' : 'bg-white text-slate-700 border-slate-200 hover:border-brand-300'"
                                        class="px-3.5 py-2 rounded-xl border text-center shrink-0 transition-all">
                                    <span class="block text-[10px] font-bold uppercase opacity-80" x-text="day.dayOfWeek"></span>
                                    <span class="block text-xs font-black" x-text="day.dayMonth"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Selected Day Banner -->
                    <div class="flex items-center justify-between border-t border-slate-200/60 pt-3 text-xs">
                        <span class="font-bold text-slate-700" x-text="'Showing time slots for: ' + selectedDayFormatted"></span>
                        <div class="flex items-center space-x-3 text-[11px]">
                            <span class="flex items-center space-x-1 text-slate-600">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                                <span>Available</span>
                            </span>
                            <span class="flex items-center space-x-1 text-slate-400">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-400 inline-block"></span>
                                <span>Reserved / Booked</span>
                            </span>
                        </div>
                    </div>

                </div>

                <!-- Slots Grid Area -->
                <div class="mt-4">
                    <!-- Loading Indicator -->
                    <div x-show="loadingSlots" class="py-12 text-center text-slate-400 space-y-2">
                        <i class="fa-solid fa-circle-notch fa-spin text-2xl text-brand-600"></i>
                        <p class="text-xs">Checking real-time doctor availability...</p>
                    </div>

                    <!-- Not a Working Day Notice -->
                    <div x-show="!loadingSlots && !isWorkday" class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-6 text-center text-xs space-y-2">
                        <i class="fa-solid fa-calendar-xmark text-amber-500 text-2xl"></i>
                        <h4 class="font-bold text-sm">Doctor Not Available on this Day</h4>
                        <p class="text-slate-600" x-text="slotMessage || 'Dr. {{ $vet->name }} does not hold consultation hours on this day. Please select another date.'"></p>
                    </div>

                    <!-- Time Slots List -->
                    <div x-show="!loadingSlots && isWorkday">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            <template x-for="slot in slots" :key="slot.time">
                                <div>
                                    <!-- Available Slot Button -->
                                    <button type="button" x-show="slot.is_available"
                                            @click="selectedTime = slot.time"
                                            :class="selectedTime === slot.time ? 'bg-brand-600 text-white border-brand-600 shadow-md ring-2 ring-brand-400/50 scale-[1.02]' : 'bg-white text-slate-800 border-slate-200 hover:border-brand-500 hover:bg-brand-50/30'"
                                            class="w-full p-3 rounded-2xl border-2 text-center transition-all flex flex-col items-center justify-center space-y-1">
                                        <div class="flex items-center space-x-1.5 font-extrabold text-sm">
                                            <i class="fa-regular fa-clock text-xs opacity-75"></i>
                                            <span x-text="slot.display_time"></span>
                                        </div>
                                        <span class="text-[10px] font-semibold"
                                              :class="selectedTime === slot.time ? 'text-brand-100' : 'text-emerald-600'">
                                            <i class="fa-solid fa-circle-check text-[8px] mr-0.5"></i>
                                            <span x-text="selectedTime === slot.time ? 'Selected' : 'Available'"></span>
                                        </span>
                                    </button>

                                    <!-- Disabled / Booked Slot (Pending or Confirmed) -->
                                    <div x-show="!slot.is_available"
                                         class="w-full p-3 rounded-2xl border border-slate-200 bg-slate-100/70 text-slate-400 text-center cursor-not-allowed opacity-75 flex flex-col items-center justify-center space-y-1 select-none"
                                         :title="slot.reason">
                                        <div class="flex items-center space-x-1.5 font-bold text-xs text-slate-400 line-through">
                                            <i class="fa-solid fa-lock text-[10px]"></i>
                                            <span x-text="slot.display_time"></span>
                                        </div>
                                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full"
                                              :class="slot.status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-700'">
                                            <span x-text="slot.status === 'pending' ? 'Pending' : (slot.status === 'past' ? 'Past' : 'Booked')"></span>
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- No slots found for workday -->
                        <div x-show="slots.length === 0" class="py-8 text-center text-slate-400 text-xs">
                            <p>No available slots for this date.</p>
                        </div>
                    </div>

                    <!-- Selected Confirmation Pill -->
                    <div x-show="selectedTime" class="mt-4 bg-emerald-50 border border-emerald-200 rounded-2xl p-3.5 flex items-center justify-between text-xs text-emerald-900">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-calendar-check text-emerald-600 text-base"></i>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-emerald-600 block">Selected Slot:</span>
                                <strong x-text="selectedDayFormatted + ' @ ' + getDisplayTime(selectedTime)"></strong>
                            </div>
                        </div>
                        <span class="text-[11px] bg-emerald-200/60 text-emerald-800 font-bold px-2.5 py-1 rounded-lg">Ready to Book</span>
                    </div>

                </div>
            </div>

            <!-- Step 4: Reason for Consultation -->
            <div class="border-t border-slate-100 pt-6">
                <label for="reason" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                    4. Reason for Consultation & Pet Symptoms *
                </label>
                <textarea name="reason" id="reason" rows="3" required
                          placeholder="Describe symptoms, duration, behavior changes, diet issues, or reason for requesting a consultation..."
                          class="w-full rounded-2xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-3 px-4 shadow-sm"></textarea>
                @error('reason') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Step 5: Optional Attachments -->
            <div>
                <label for="attachments" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                    5. Upload Photos or Documents (Optional)
                </label>
                <input type="file" name="attachments[]" id="attachments" multiple accept="image/*,.pdf"
                       class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 border border-slate-200 rounded-2xl">
                <p class="text-[11px] text-slate-400 mt-1">Upload clear photos of affected areas, vaccination records, or previous prescriptions.</p>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-slate-100">
                <button type="submit"
                        :disabled="!selectedTime || !hasSufficientCredits"
                        :class="(!selectedTime || !hasSufficientCredits) ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-brand-600 hover:bg-brand-700 shadow-lg shadow-brand-600/30'"
                        class="w-full text-white font-bold py-4 rounded-2xl transition-all text-sm flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span x-text="!hasSufficientCredits ? ('Insufficient Credits (' + totalCreditsCost + ' required)') : (selectedTime ? ('Submit Request (' + (extraPetCount + 1) + ' Pet' + (extraPetCount > 0 ? 's' : '') + ' • ' + totalCreditsCost + ' Credits)') : 'Please Select a Time Slot')"></span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function bookingCalendarComponent(vetId, baseCreditsCost, addCreditsCost, clientCredits, baseFee, addFee, baseDuration, addDuration, initialPetId) {
        return {
            vetId: vetId,
            baseCreditsCost: Number(baseCreditsCost),
            addCreditsCost: Number(addCreditsCost),
            clientCredits: Number(clientCredits),
            baseFee: Number(baseFee),
            addFee: Number(addFee),
            baseDuration: Number(baseDuration),
            addDuration: Number(addDuration),
            primaryPetId: initialPetId,
            selectedAdditionalPetIds: [],
            selectedDate: '{{ date("Y-m-d") }}',
            selectedTime: '',
            selectedDayFormatted: '',
            slots: [],
            isWorkday: true,
            slotMessage: '',
            loadingSlots: false,
            pollTimer: null,
            upcomingDays: [],

            get extraPetCount() {
                return this.selectedAdditionalPetIds.length;
            },

            get totalFee() {
                return this.baseFee + (this.extraPetCount * this.addFee);
            },

            get totalDuration() {
                return this.baseDuration + (this.extraPetCount * this.addDuration);
            },

            get totalCreditsCost() {
                return this.baseCreditsCost + (this.extraPetCount * this.addCreditsCost);
            },

            get hasSufficientCredits() {
                return this.clientCredits >= this.totalCreditsCost;
            },

            onPrimaryPetChange() {
                this.selectedAdditionalPetIds = this.selectedAdditionalPetIds.filter(id => String(id) !== String(this.primaryPetId));
            },

            init() {
                this.generateUpcomingDays();
                this.fetchSlots();

                // Real-time polling every 6 seconds to detect new bookings in real time
                this.pollTimer = setInterval(() => {
                    if (this.selectedDate) {
                        this.fetchSlots(true); // silent refresh
                    }
                }, 6000);
            },

            generateUpcomingDays() {
                const days = [];
                const now = new Date();
                for (let i = 0; i < 7; i++) {
                    const d = new Date();
                    d.setDate(now.getDate() + i);
                    const yyyy = d.getFullYear();
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const dd = String(d.getDate()).padStart(2, '0');
                    const dateStr = `${yyyy}-${mm}-${dd}`;
                    const dayOfWeek = d.toLocaleDateString('en-US', { weekday: 'short' });
                    const dayMonth = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    days.push({ date: dateStr, dayOfWeek, dayMonth });
                }
                this.upcomingDays = days;
            },

            onDateChange() {
                this.selectedTime = '';
                this.fetchSlots();
            },

            fetchSlots(silent = false) {
                if (!silent) {
                    this.loadingSlots = true;
                }

                fetch(`/client/vets/${this.vetId}/slots?date=${this.selectedDate}`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    this.loadingSlots = false;
                    this.isWorkday = data.is_workday;
                    this.slotMessage = data.message || '';
                    this.selectedDayFormatted = data.day_name || this.selectedDate;
                    this.slots = data.slots || [];

                    // If previously selected time was booked by someone else in real time, unselect and notify!
                    if (this.selectedTime) {
                        const currentSlot = this.slots.find(s => s.time === this.selectedTime);
                        if (currentSlot && !currentSlot.is_available) {
                            this.selectedTime = '';
                            alert('Notice: The time slot you had highlighted was just reserved by another client. Please select another slot.');
                        }
                    }
                })
                .catch(err => {
                    this.loadingSlots = false;
                    console.error('Error loading slots:', err);
                });
            },

            getDisplayTime(timeStr) {
                const slot = this.slots.find(s => s.time === timeStr);
                return slot ? slot.display_time : timeStr;
            },

            validateSubmission(e) {
                if (!this.selectedTime) {
                    e.preventDefault();
                    alert('Please select an available time slot from the calendar.');
                    return false;
                }
                if (!this.hasSufficientCredits) {
                    e.preventDefault();
                    alert(`You have insufficient credits. You need ${this.totalCreditsCost} credits for this consultation.`);
                    return false;
                }
                return true;
            }
        }
    }
</script>
@endpush
@endsection
