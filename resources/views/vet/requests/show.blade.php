@extends('layouts.app')

@section('title', 'Consultation Request Details — #' . $consultation->consultation_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{
    rescheduleModalOpen: false,
    declineModalOpen: false,
    addFreeTimeModalOpen: false,
    suggestedDate: '{{ date('Y-m-d', strtotime('+1 day')) }}',
    suggestedTime: '10:00:00',
    rescheduleNote: ''
}">

    <!-- Top Status Banner -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="flex items-center space-x-2">
                <span class="text-xs uppercase font-extrabold px-3 py-1 rounded-full 
                    @if($consultation->status === 'accepted') bg-emerald-100 text-emerald-800
                    @elseif($consultation->status === 'pending') bg-amber-100 text-amber-800
                    @elseif($consultation->status === 'reschedule_suggested') bg-indigo-100 text-indigo-800
                    @elseif($consultation->status === 'completed') bg-slate-100 text-slate-800
                    @else bg-rose-100 text-rose-800 @endif">
                    {{ ucfirst(str_replace('_', ' ', $consultation->status)) }}
                </span>
                <span class="text-xs text-slate-400 font-mono">#{{ $consultation->consultation_number }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-800 mt-2">Request from {{ $consultation->client->name }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                Requested Schedule: <strong class="text-slate-700">{{ $consultation->scheduled_at->format('F d, Y @ g:i A') }}</strong> • 
                Mode: <span class="capitalize font-bold text-brand-600">{{ $consultation->type }}</span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($consultation->status === 'pending')
                <!-- Accept Button -->
                <form method="POST" action="{{ route('vet.requests.accept', $consultation) }}" onsubmit="return confirm('Accept this consultation? {{ $consultation->credits_cost ?: $bookingCreditsCost }} credits will be deducted from client.');">
                    @csrf
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center space-x-1.5">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Accept Request (Deduct {{ $consultation->credits_cost ?: $bookingCreditsCost }} Credits)</span>
                    </button>
                </form>

                <!-- Suggest Another Time Slot Button -->
                <button type="button" @click="rescheduleModalOpen = true" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md shadow-indigo-600/20 transition-all flex items-center space-x-1.5">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Suggest Another Slot</span>
                </button>

                <!-- Decline Button -->
                <button type="button" @click="declineModalOpen = true" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs px-3.5 py-2.5 rounded-xl transition-all">
                    Decline
                </button>
            @endif

            @if(in_array($consultation->status, ['accepted', 'in_progress', 'completed']))
                <!-- Doctor Add Free Time Button -->
                <button type="button" @click="addFreeTimeModalOpen = true"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-2.5 rounded-xl shadow-md shadow-emerald-600/20 flex items-center space-x-1.5 transition-all"
                        title="Add complimentary consultation time (free of charge to client)">
                    <i class="fa-solid fa-gift"></i>
                    <span>+ Free Time</span>
                </button>
            @endif

            @if(in_array($consultation->status, ['accepted', 'in_progress']))
                @if($consultation->type === 'video')
                    <a href="{{ route('consultation.video', $consultation) }}" class="bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md shadow-brand-600/30 flex items-center space-x-1.5 transition-all">
                        <i class="fa-solid fa-video"></i>
                        <span>Start Video Call</span>
                    </a>
                @endif
                <a href="{{ route('consultation.chat', $consultation) }}" class="bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs px-4 py-2.5 rounded-xl flex items-center space-x-1.5 transition-all">
                    <i class="fa-solid fa-comments"></i>
                    <span>Open Chat Room</span>
                </a>
                <a href="{{ route('vet.records.create', $consultation) }}" class="bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all">
                    Write Clinical Notes
                </a>
            @endif
        </div>
    </div>

    <!-- Pending Extension Request Banner (Vet Review Card) -->
    @if($consultation->pendingTimeExtension)
        <div class="bg-amber-50 border-2 border-amber-300 rounded-3xl p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs uppercase font-extrabold px-3 py-1 rounded-full bg-amber-200 text-amber-900 flex items-center space-x-1.5">
                            <i class="fa-solid fa-hourglass-start"></i>
                            <span>Extension Requested by Client</span>
                        </span>
                        <span class="text-xs text-amber-700 font-medium">{{ $consultation->pendingTimeExtension->created_at->diffForHumans() }}</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mt-1">
                        {{ $consultation->client->name }} requested +{{ $consultation->pendingTimeExtension->minutes }} Minutes Extension
                    </h3>
                    <p class="text-xs text-slate-600">
                        If approved, <strong class="text-amber-800">{{ $consultation->pendingTimeExtension->credits_cost }} credits</strong> will be deducted from the client's balance and consultation duration will extend to {{ ($consultation->duration_minutes ?: 15) + $consultation->pendingTimeExtension->minutes }} minutes.
                    </p>
                </div>
                <div class="flex items-center space-x-2.5 shrink-0">
                    <form method="POST" action="{{ route('consultation.extensions.approve', [$consultation, $consultation->pendingTimeExtension]) }}" onsubmit="return confirm('Approve +{{ $consultation->pendingTimeExtension->minutes }} minutes extension? {{ $consultation->pendingTimeExtension->credits_cost }} credits will be deducted from client.');">
                        @csrf
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md shadow-emerald-600/20 flex items-center space-x-1.5 transition-all">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Approve (+{{ $consultation->pendingTimeExtension->minutes }}m)</span>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('consultation.extensions.decline', [$consultation, $consultation->pendingTimeExtension]) }}" onsubmit="return confirm('Decline this time extension request?');">
                        @csrf
                        <button type="submit" class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs px-4 py-2.5 rounded-xl flex items-center space-x-1.5 transition-all">
                            <i class="fa-solid fa-circle-xmark"></i>
                            <span>Decline</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Notice if Reschedule was Proposed -->
    @if($consultation->status === 'reschedule_suggested')
        <div class="bg-indigo-50 border border-indigo-200 rounded-3xl p-6 text-xs text-indigo-900 shadow-sm space-y-2">
            <div class="flex items-center space-x-2 font-bold text-sm text-indigo-800">
                <i class="fa-solid fa-calendar-clock text-indigo-600 text-base"></i>
                <span>You Suggested a New Consultation Schedule</span>
            </div>
            <div class="bg-white/80 p-4 rounded-2xl border border-indigo-100 space-y-1.5">
                <p><strong>Proposed Date & Time:</strong> {{ $consultation->suggested_scheduled_at ? $consultation->suggested_scheduled_at->format('F d, Y @ g:i A') : 'N/A' }}</p>
                <p><strong>Doctor Note Sent to Client:</strong> "{{ $consultation->reschedule_note }}"</p>
                <p class="text-slate-500 pt-1">• Waiting for the client to confirm or decline this suggested schedule. Credits will be deducted automatically once the client confirms.</p>
            </div>
        </div>
    @endif

    <!-- Confirmed Credits Banner -->
    @if($consultation->credits_deducted > 0)
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-xs text-emerald-800 flex items-center justify-between">
            <div class="flex items-center space-x-2 font-semibold">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span>Booking Confirmed • {{ $consultation->credits_deducted }} consultation credits have been deducted from client.</span>
            </div>
            <span class="font-mono font-extrabold bg-emerald-100 px-2.5 py-1 rounded-lg">{{ $consultation->credits_deducted }} credits</span>
        </div>
    @endif

    <!-- Pet Details & History Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-lg font-bold text-slate-800 flex items-center">
                <i class="fa-solid fa-paw text-brand-600 mr-2"></i> Patient(s) for this Consultation ({{ $consultation->all_pets->count() }})
            </h2>
            <div class="text-xs text-slate-600 flex items-center space-x-3">
                <span>Total Time: <strong class="text-slate-800">{{ $consultation->duration_minutes ?: 15 }} mins</strong></span>
                <span>•</span>
                <span>Consumed: <strong class="text-amber-700">{{ $consultation->formatted_consumed_time }}</strong></span>
                <span>•</span>
                <span>Remaining: <strong class="text-emerald-700">{{ $consultation->formatted_remaining_time }}</strong></span>
                <span>•</span>
                <span>Total Fee: <strong class="text-emerald-700">₱{{ number_format($consultation->fee, 2) }}</strong></span>
            </div>
        </div>

        <div class="space-y-4">
            @foreach($consultation->all_pets as $pet)
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-8 h-8 rounded-lg bg-brand-100 text-brand-700 font-bold flex items-center justify-center text-xs">
                                <i class="fa-solid {{ $pet->animalType ? $pet->animalType->icon : 'fa-paw' }}"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-slate-800 text-sm">{{ $pet->name }}</h3>
                                <span class="text-[11px] text-slate-500">{{ $pet->animalType->name ?? 'Pet' }} • {{ $pet->breed_name }}</span>
                            </div>
                        </div>
                        @if($pet->id === $consultation->pet_id)
                            <span class="text-[10px] bg-brand-600 text-white font-bold px-2 py-0.5 rounded-full">Primary Pet</span>
                        @else
                            <span class="text-[10px] bg-slate-200 text-slate-700 font-bold px-2 py-0.5 rounded-full">Additional Pet</span>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-[11px] bg-white p-3 rounded-xl border border-slate-200">
                        <div><span class="text-slate-400 block">Sex</span><strong class="text-slate-700">{{ $pet->sex }}</strong></div>
                        <div><span class="text-slate-400 block">Age</span><strong class="text-slate-700">{{ $pet->age_text ?: 'N/A' }}</strong></div>
                        <div><span class="text-slate-400 block">Weight</span><strong class="text-slate-700">{{ $pet->weight ?: 'N/A' }}</strong></div>
                        <div><span class="text-slate-400 block">Color</span><strong class="text-slate-700">{{ $pet->color ?: 'N/A' }}</strong></div>
                    </div>

                    @if($pet->allergies || $pet->existing_conditions || $pet->vaccination_info)
                        <div class="text-xs space-y-1 pt-1 text-slate-600 border-t border-slate-200/50">
                            @if($pet->allergies)
                                <p><strong class="text-rose-600">Allergies:</strong> {{ $pet->allergies }}</p>
                            @endif
                            @if($pet->existing_conditions)
                                <p><strong class="text-amber-600">Existing Conditions:</strong> {{ $pet->existing_conditions }}</p>
                            @endif
                            @if($pet->vaccination_info)
                                <p><strong class="text-emerald-700">Vaccinations:</strong> {{ $pet->vaccination_info }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="space-y-2 text-xs text-slate-700 border-t border-slate-100 pt-4">
            <strong class="text-slate-800 block">Reason for Consultation:</strong>
            <p class="bg-slate-50 p-4 rounded-2xl border border-slate-200 leading-relaxed">{{ $consultation->reason }}</p>
        </div>

        <!-- Attachments -->
        @if($consultation->attachments && count($consultation->attachments) > 0)
            <div class="pt-2">
                <strong class="text-slate-800 text-xs block mb-2">Uploaded Attachments from Client:</strong>
                <div class="flex flex-wrap gap-2">
                    @foreach($consultation->attachments as $path)
                        <a href="{{ asset('storage/' . $path) }}" target="_blank" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs px-3 py-2 rounded-xl flex items-center space-x-1.5 transition-colors">
                            <i class="fa-solid fa-paperclip text-slate-400"></i>
                            <span>View Attachment</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Modal: Doctor Suggests Another Time Slot -->
    <div x-show="rescheduleModalOpen"
         x-transition class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         style="display: none;">
        <div @click.outside="rescheduleModalOpen = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-100 space-y-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-calendar-plus"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">Suggest Another Time Slot</h3>
                        <p class="text-xs text-slate-500">Client will receive your proposal and must confirm it</p>
                    </div>
                </div>
                <button @click="rescheduleModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 text-xs text-slate-600">
                Current Requested Time: <strong class="text-slate-800">{{ $consultation->scheduled_at->format('F d, Y @ g:i A') }}</strong>
            </div>

            <form method="POST" action="{{ route('vet.requests.suggest-reschedule', $consultation) }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="suggested_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">New Date *</label>
                        <input type="date" name="suggested_date" id="suggested_date" x-model="suggestedDate" min="{{ date('Y-m-d') }}" required
                               class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs py-2.5 px-3.5">
                    </div>
                    <div>
                        <label for="suggested_time" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">New Time *</label>
                        <select name="suggested_time" id="suggested_time" x-model="suggestedTime" required
                                class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs py-2.5 px-3.5">
                            <option value="09:00:00">09:00 AM</option>
                            <option value="10:00:00">10:00 AM</option>
                            <option value="11:00:00">11:00 AM</option>
                            <option value="13:00:00">01:00 PM</option>
                            <option value="14:00:00">02:00 PM</option>
                            <option value="15:00:00">03:00 PM</option>
                            <option value="16:00:00">04:00 PM</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="reschedule_note" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Reason / Note to Client *</label>
                    <textarea name="reschedule_note" id="reschedule_note" x-model="rescheduleNote" rows="3" required
                              placeholder="e.g. I have an emergency surgery in the morning, can we consult at 2:00 PM instead?"
                              class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs py-2.5 px-3.5"></textarea>
                </div>

                <div class="bg-indigo-50/60 border border-indigo-200/80 rounded-2xl p-3 text-[11px] text-indigo-900">
                    <i class="fa-solid fa-circle-info mr-1"></i>
                    Credits ({{ $bookingCreditsCost }}) will remain with the client until they approve this new schedule.
                </div>

                <div class="flex items-center justify-end space-x-3 pt-2">
                    <button type="button" @click="rescheduleModalOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20">Send Proposal to Client</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Decline Request -->
    <div x-show="declineModalOpen"
         x-transition class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         style="display: none;">
        <div @click.outside="declineModalOpen = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
            <h3 class="font-bold text-slate-800 text-base">Decline Consultation Request</h3>
            <p class="text-xs text-slate-500">Please provide a reason why you cannot accept this request.</p>

            <form method="POST" action="{{ route('vet.requests.decline', $consultation) }}" class="space-y-4">
                @csrf
                <textarea name="decline_reason" rows="3" required placeholder="State the reason for declining..."
                          class="w-full rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 text-xs py-2.5 px-3.5"></textarea>
                <div class="flex justify-end space-x-2">
                    <button type="button" @click="declineModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold">Confirm Decline</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Doctor Add Free Time -->
    <div x-show="addFreeTimeModalOpen"
         x-transition class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         style="display: none;">
        <div @click.outside="addFreeTimeModalOpen = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-100 space-y-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-gift"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">Add Complimentary Time</h3>
                        <p class="text-xs text-slate-500">Free of charge to the client</p>
                    </div>
                </div>
                <button @click="addFreeTimeModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <p class="text-xs text-slate-600">
                Current Duration: <strong class="text-slate-800">{{ $consultation->duration_minutes ?: 15 }} mins</strong>. Choose how many extra minutes to grant free of charge:
            </p>

            <form method="POST" action="{{ route('consultation.doctor-add-time', $consultation) }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-3 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="minutes" value="5" class="peer sr-only" checked>
                        <div class="p-3 text-center rounded-xl border border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 font-bold text-xs text-slate-700 peer-checked:text-emerald-700">
                            +5 Mins
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="minutes" value="10" class="peer sr-only">
                        <div class="p-3 text-center rounded-xl border border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 font-bold text-xs text-slate-700 peer-checked:text-emerald-700">
                            +10 Mins
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="minutes" value="15" class="peer sr-only">
                        <div class="p-3 text-center rounded-xl border border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 font-bold text-xs text-slate-700 peer-checked:text-emerald-700">
                            +15 Mins
                        </div>
                    </label>
                </div>

                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-[11px] text-emerald-800">
                    <i class="fa-solid fa-circle-info mr-1"></i>
                    This immediately extends consultation time and does <strong>not</strong> deduct any credits from {{ $consultation->client->name }}.
                </div>

                <div class="flex items-center justify-end space-x-3 pt-2">
                    <button type="button" @click="addFreeTimeModalOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20">Grant Free Time</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
