@extends('layouts.app')

@section('title', 'Consultation #' . $consultation->consultation_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Status Header Banner -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="flex items-center space-x-3">
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
            <h1 class="text-2xl font-bold text-slate-800 mt-2">Consultation with Dr. {{ $consultation->vet->name }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                Pet: <strong class="text-slate-700">{{ $consultation->pet->name }}</strong> • 
                Scheduled for <strong class="text-slate-700">{{ $consultation->scheduled_at->format('F d, Y @ g:i A') }}</strong>
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-3">
            @if(in_array($consultation->status, ['accepted', 'scheduled', 'in_progress', 'completed']))
                @if($consultation->type === 'video')
                    <a href="{{ route('consultation.video', $consultation) }}" class="bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs px-5 py-3 rounded-2xl shadow-md shadow-brand-600/30 transition-all flex items-center space-x-2">
                        <i class="fa-solid fa-video"></i>
                        <span>Enter Video Room</span>
                    </a>
                @endif
                <a href="{{ route('consultation.chat', $consultation) }}" class="bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs px-5 py-3 rounded-2xl transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-comments"></i>
                    <span>Open Chat Room</span>
                </a>
            @endif

            @if($consultation->status === 'pending')
                <form method="POST" action="{{ route('client.bookings.cancel', $consultation) }}" onsubmit="return confirm('Cancel this consultation request?');">
                    @csrf
                    <button type="submit" class="bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-xs px-4 py-2.5 rounded-xl border border-rose-200 transition-all">
                        Cancel Request
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Doctor Proposed Reschedule Banner (CRITICAL USER INTERACTION) -->
    @if($consultation->status === 'reschedule_suggested')
        <div class="bg-gradient-to-r from-indigo-900 to-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-indigo-700/80 space-y-5">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 flex items-center justify-center text-2xl animate-bounce">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div>
                    <span class="text-[11px] uppercase tracking-wider text-indigo-300 font-extrabold">Doctor Proposed A New Schedule</span>
                    <h2 class="text-xl font-bold text-white">Dr. {{ $consultation->vet->name }} suggested a new time slot</h2>
                </div>
            </div>

            <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl p-5 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block mb-0.5">Original Requested Time:</span>
                        <span class="text-slate-300 font-mono line-through">{{ $consultation->scheduled_at->format('F d, Y @ g:i A') }}</span>
                    </div>
                    <div>
                        <span class="text-indigo-400 font-bold block mb-0.5">Proposed New Time Slot:</span>
                        <strong class="text-emerald-400 font-mono text-sm">{{ $consultation->suggested_scheduled_at ? $consultation->suggested_scheduled_at->format('F d, Y @ g:i A') : 'N/A' }}</strong>
                    </div>
                </div>

                @if($consultation->reschedule_note)
                    <div class="border-t border-slate-700/60 pt-3 text-xs">
                        <span class="text-slate-400 block font-semibold mb-1">Doctor's Note / Explanation:</span>
                        <p class="text-slate-200 bg-slate-900/60 p-3 rounded-xl border border-slate-700/50 italic">"{{ $consultation->reschedule_note }}"</p>
                    </div>
                @endif

                <div class="border-t border-slate-700/60 pt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <p class="text-slate-300">
                        <i class="fa-solid fa-circle-info text-amber-400 mr-1"></i>
                        Confirming will deduct <strong>{{ $bookingCreditsCost }} credits</strong> from your balance. (Current balance: <strong>{{ number_format(auth()->user()->credits ?? 0) }} credits</strong>).
                    </p>

                    <div class="flex items-center space-x-3 shrink-0">
                        <!-- Decline Button -->
                        <form method="POST" action="{{ route('client.bookings.decline-reschedule', $consultation) }}" onsubmit="return confirm('Decline this proposal and cancel the request?');">
                            @csrf
                            <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold transition-all">
                                Decline
                            </button>
                        </form>

                        <!-- Accept Button -->
                        <form method="POST" action="{{ route('client.bookings.accept-reschedule', $consultation) }}" onsubmit="return confirm('Agree to this new schedule and confirm consultation? {{ $bookingCreditsCost }} credits will be deducted.');">
                            @csrf
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-extrabold shadow-lg shadow-emerald-500/30 transition-all flex items-center space-x-1.5">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Agree & Confirm (Deduct {{ $bookingCreditsCost }} Credits)</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Confirmed Booking Credits Notice -->
    @if($consultation->credits_deducted > 0)
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-xs text-emerald-900 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-coins text-emerald-600 text-lg"></i>
                <div>
                    <strong class="block font-bold">Booking Confirmed & Credits Deducted</strong>
                    <span class="text-slate-600">{{ $consultation->credits_deducted }} credits were deducted from your balance upon doctor confirmation.</span>
                </div>
            </div>
            <span class="font-mono font-black text-emerald-700 bg-emerald-100 px-3 py-1 rounded-xl text-sm">-{{ $consultation->credits_deducted }} pts</span>
        </div>
    @endif

    <!-- Booking Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols -->
        <div class="md:col-span-2 space-y-6">
            
            <!-- Reason for Consultation -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Reason for Consultation</h3>
                <p class="text-xs text-slate-700 bg-slate-50 p-4 rounded-2xl border border-slate-100 leading-relaxed">{{ $consultation->reason }}</p>

                @if($consultation->attachments && count($consultation->attachments) > 0)
                    <div class="mt-4">
                        <span class="text-xs font-semibold text-slate-700 block mb-2">Uploaded Attachments:</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach($consultation->attachments as $path)
                                <a href="{{ asset('storage/' . $path) }}" target="_blank" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs px-3 py-2 rounded-xl flex items-center space-x-1.5 transition-colors">
                                    <i class="fa-solid fa-paperclip text-slate-400"></i>
                                    <span>Attachment</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Medical Record (if completed) -->
            @if($consultation->record)
                <div class="bg-emerald-50/50 border border-emerald-200/80 rounded-3xl p-6 sm:p-8 shadow-sm space-y-4">
                    <div class="flex items-center space-x-2 text-emerald-800">
                        <i class="fa-solid fa-clipboard-check text-emerald-600 text-lg"></i>
                        <h3 class="font-bold text-base">Veterinarian Clinical Record</h3>
                    </div>

                    <div class="space-y-3 text-xs text-slate-700">
                        @if($consultation->record->symptoms)
                            <div><strong class="text-slate-900">Symptoms Observed:</strong> <p class="mt-0.5">{{ $consultation->record->symptoms }}</p></div>
                        @endif
                        @if($consultation->record->assessment)
                            <div><strong class="text-slate-900">Clinical Diagnosis / Assessment:</strong> <p class="mt-0.5">{{ $consultation->record->assessment }}</p></div>
                        @endif
                        @if($consultation->record->recommendations)
                            <div><strong class="text-slate-900">Recommendations & Treatment:</strong> <p class="mt-0.5">{{ $consultation->record->recommendations }}</p></div>
                        @endif
                        @if($consultation->record->medication_info)
                            <div class="bg-white p-4 rounded-2xl border border-emerald-200">
                                <strong class="text-emerald-900 flex items-center"><i class="fa-solid fa-pills mr-1"></i> Prescribed Medication:</strong>
                                <p class="mt-1 font-medium">{{ $consultation->record->medication_info }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

        </div>

        <!-- Right Col: Vet & Pet Overview -->
        <div class="space-y-6">
            <!-- Vet Details Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-3">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Assigned Veterinarian</h3>
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-2xl bg-slate-800 text-brand-400 font-bold flex items-center justify-center text-lg">
                        {{ strtoupper(substr($consultation->vet->name, 4, 1)) }}
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">Dr. {{ $consultation->vet->name }}</h4>
                        <p class="text-[11px] text-slate-500">{{ $consultation->vet->vetProfile->clinic_name ?: 'Teleconsult Clinic' }}</p>
                    </div>
                </div>
            </div>

            <!-- Pet Details Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-3">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pet Patient</h3>
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 font-bold flex items-center justify-center border border-brand-100 text-lg">
                        <i class="fa-solid {{ $consultation->pet->animalType ? $consultation->pet->animalType->icon : 'fa-paw' }}"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">{{ $consultation->pet->name }}</h4>
                        <p class="text-[11px] text-slate-500">{{ $consultation->pet->breed_name }}</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
