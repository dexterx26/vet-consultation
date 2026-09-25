@extends('layouts.app')

@section('title', 'Consultation #' . $consultation->consultation_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="clientBookingShowComponent()">

    <!-- Real-Time Floating Toast Alert when Doctor Declines -->
    <div x-show="isDeclinedToast" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="-translate-y-4 opacity-0 scale-95"
         x-transition:enter-end="translate-y-0 opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-y-0 opacity-100 scale-100"
         x-transition:leave-end="-translate-y-4 opacity-0 scale-95"
         class="fixed top-6 right-6 z-50 max-w-md w-full bg-slate-900/95 backdrop-blur-md text-white rounded-3xl p-5 shadow-2xl border border-rose-500/50 ring-1 ring-rose-500/30"
         x-cloak>
        <div class="flex items-start space-x-3.5">
            <div class="w-10 h-10 rounded-2xl bg-rose-500 text-white flex items-center justify-center text-lg shrink-0 shadow-lg shadow-rose-500/30 animate-pulse">
                <i class="fa-solid fa-ban"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-rose-400 bg-rose-950/80 px-2 py-0.5 rounded-full border border-rose-500/40">Real-Time Update</span>
                    <button @click="isDeclinedToast = false" class="text-slate-400 hover:text-white transition-colors text-xs p-1">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <h3 class="text-sm font-extrabold text-white mt-1">Consultation Request Declined</h3>
                <p class="text-xs text-slate-300 mt-0.5">
                    Dr. <strong class="text-white" x-text="vetName"></strong> was unable to accept your booking request.
                </p>
                <div class="text-[11px] text-rose-200 italic mt-1 bg-rose-950/40 p-2.5 rounded-xl border border-rose-500/30" 
                     x-show="declineReason">
                    <span x-text="'&quot;' + declineReason + '&quot;'"></span>
                </div>
                <div class="mt-2.5 pt-2 border-t border-white/10 flex items-center justify-between text-xs">
                    <span class="text-emerald-400 text-[11px]"><i class="fa-solid fa-check mr-1"></i> No credits were deducted</span>
                    <button @click="isDeclinedToast = false" class="text-white bg-white/10 hover:bg-white/20 px-2.5 py-1 rounded-lg text-xs font-semibold">
                        Dismiss
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Header Banner -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="flex items-center space-x-3 flex-wrap gap-y-1">
                @if($consultation->is_follow_up)
                    <span class="text-xs uppercase font-extrabold px-3 py-1 rounded-full bg-teal-100 text-teal-800 border border-teal-200 flex items-center space-x-1">
                        <i class="fa-solid fa-calendar-check text-[10px]"></i>
                        <span>Follow-up Checkup</span>
                    </span>
                @endif
                <span class="text-xs uppercase font-extrabold px-3 py-1 rounded-full transition-all flex items-center space-x-1.5" 
                    :class="{
                        'bg-emerald-100 text-emerald-800': status === 'accepted',
                        'bg-amber-100 text-amber-800': status === 'pending',
                        'bg-indigo-100 text-indigo-800': status === 'reschedule_suggested',
                        'bg-slate-100 text-slate-800': status === 'completed',
                        'bg-rose-100 text-rose-800': status === 'declined' || status.includes('cancelled')
                    }">
                    <span class="w-1.5 h-1.5 rounded-full"
                          :class="{
                              'bg-emerald-500': status === 'accepted',
                              'bg-amber-500 animate-pulse': status === 'pending',
                              'bg-indigo-500': status === 'reschedule_suggested',
                              'bg-slate-500': status === 'completed',
                              'bg-rose-500': status === 'declined' || status.includes('cancelled')
                          }"></span>
                    <span x-text="status.replace(/_/g, ' ')">
                        {{ ucfirst(str_replace('_', ' ', $consultation->status)) }}
                    </span>
                </span>
                <span class="text-xs text-slate-400 font-mono">#{{ $consultation->consultation_number }}</span>
                <span x-show="isFetchingStatus" class="text-[10px] text-brand-600 animate-pulse font-semibold" x-cloak>
                    <i class="fa-solid fa-rotate fa-spin mr-0.5"></i> Syncing...
                </span>
            </div>
            <h1 class="text-2xl font-bold text-slate-800 mt-2">
                @if($consultation->is_follow_up) Follow-up Checkup with @else Consultation with @endif Dr. {{ $consultation->vet->name }}
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Pets: <strong class="text-slate-700">{{ $consultation->all_pets->pluck('name')->join(', ') }}</strong> • 
                Duration: <strong class="text-slate-700">{{ $consultation->duration_minutes ?: 15 }} mins</strong> • 
                Fee: 
                @if(($consultation->credits_cost ?? 0) > 0)
                    <strong class="text-emerald-700">₱{{ number_format($consultation->fee, 2) }} ({{ $consultation->credits_cost }} credits)</strong> • 
                @else
                    <strong class="text-emerald-700 font-bold">Complimentary (0 Credits)</strong> • 
                @endif
                Scheduled for <strong class="text-slate-700">{{ $consultation->scheduled_at->format('F d, Y @ g:i A') }}</strong>
                @if($consultation->is_follow_up)
                    <span class="text-teal-700 font-bold">(Fixed by Doctor)</span>
                @endif
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-3">
            <template x-if="['accepted', 'scheduled', 'in_progress', 'completed'].includes(status)">
                <div class="flex items-center space-x-3">
                    @if(!$consultation->pendingTimeExtension)
                        <button type="button" x-show="['in_progress', 'completed'].includes(status)" @click="showExtensionModal = true" class="bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs px-4 py-3 rounded-2xl shadow-md shadow-amber-600/30 transition-all flex items-center space-x-2">
                            <i class="fa-solid fa-hourglass-start"></i>
                            <span>+ Add Time</span>
                        </button>
                    @endif
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
                </div>
            </template>

            <template x-if="status === 'pending'">
                <div class="flex items-center space-x-2">
                    @if($consultation->is_follow_up)
                        <form method="POST" action="{{ route('client.bookings.decline-follow-up', $consultation) }}" onsubmit="return confirm('Decline this follow-up checkup?');">
                            @csrf
                            <button type="submit" class="bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-xs px-4 py-2.5 rounded-xl border border-rose-200 transition-all">
                                Decline
                            </button>
                        </form>
                        <form method="POST" action="{{ route('client.bookings.accept-follow-up', $consultation) }}" onsubmit="return confirm('Approve this follow-up checkup? {{ ($consultation->credits_cost ?? 0) > 0 ? $consultation->credits_cost . \" credits will be deducted.\" : \"No credits will be deducted (Free).\" }}');">
                            @csrf
                            <button type="submit"
                                    @if(($consultation->credits_cost ?? 0) > 0 && ($userCredits < $consultation->credits_cost)) disabled @endif
                                    class="bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md shadow-teal-600/30 transition-all flex items-center space-x-1.5">
                                <i class="fa-solid fa-check-circle"></i>
                                <span>Approve Checkup ({{ ($consultation->credits_cost ?? 0) > 0 ? $consultation->credits_cost . ' Cr' : 'Free' }})</span>
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('client.bookings.cancel', $consultation) }}" onsubmit="return confirm('Cancel this consultation request?');">
                            @csrf
                            <button type="submit" class="bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-xs px-4 py-2.5 rounded-xl border border-rose-200 transition-all">
                                Cancel Request
                            </button>
                        </form>
                    @endif
                </div>
            </template>

            <template x-if="status === 'declined'">
                <div class="flex items-center space-x-2">
                    <a href="{{ route('client.vets.search') }}" class="bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition-all flex items-center space-x-1.5">
                        <i class="fa-solid fa-calendar-plus"></i>
                        <span>Book Another Vet</span>
                    </a>
                </div>
            </template>
        </div>
    </div>

    <!-- Real-Time Doctor Declined Consultation Banner -->
    <div x-show="status === 'declined'" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="-translate-y-3 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         class="bg-gradient-to-r from-rose-50 via-rose-50/60 to-white border-2 border-rose-300 rounded-3xl p-6 sm:p-7 shadow-sm space-y-4"
         @if($consultation->status !== 'declined') x-cloak @endif>
        <div class="flex items-start space-x-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-500 text-white flex items-center justify-center text-2xl shrink-0 shadow-lg shadow-rose-500/20">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-[11px] uppercase font-extrabold px-2.5 py-0.5 rounded-full bg-rose-200 text-rose-900 border border-rose-300">
                        Consultation Request Declined
                    </span>
                    <span class="text-xs text-rose-700 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-bolt text-rose-500"></i>
                        <span>Live Updated via Reverb</span>
                    </span>
                </div>
                <h2 class="text-lg font-extrabold text-slate-900 mt-1 font-heading">
                    Dr. <span x-text="vetName">{{ $consultation->vet->name }}</span> was unable to accept this consultation request
                </h2>
                
                <div class="mt-3 p-4 bg-white rounded-2xl border border-rose-200/90 shadow-sm text-xs space-y-1">
                    <strong class="text-rose-900 font-bold block flex items-center gap-1.5">
                        <i class="fa-solid fa-comment-dots text-rose-500"></i>
                        <span>Doctor's Reason / Explanation:</span>
                    </strong>
                    <p class="italic text-slate-700 bg-rose-50/40 p-2.5 rounded-xl border border-rose-100 font-medium" 
                       x-text="declineReason || 'No specific explanation was provided.'">
                        {{ $consultation->decline_reason ?: 'No specific explanation was provided.' }}
                    </p>
                </div>

                <div class="mt-3 flex items-center space-x-2 text-xs text-emerald-800 bg-emerald-50 border border-emerald-200 p-3 rounded-2xl">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm shrink-0"></i>
                    <span><strong>No credits deducted:</strong> Your balance remains intact. You can select another schedule or book another veterinarian.</span>
                </div>

                <div class="pt-4 flex flex-wrap items-center gap-3">
                    <a href="{{ route('client.vets.search') }}" class="inline-flex items-center space-x-2 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-md shadow-brand-600/20 transition-all">
                        <i class="fa-solid fa-user-doctor"></i>
                        <span>Find Another Veterinarian</span>
                    </a>
                    <a href="{{ route('client.bookings.index') }}" class="inline-flex items-center space-x-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs px-4 py-2.5 rounded-xl transition-colors">
                        <span>View My Consultations</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Time Extension Banner for Client -->
    @if($consultation->pendingTimeExtension)
        <div class="bg-amber-50 border-2 border-amber-300 rounded-3xl p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs uppercase font-extrabold px-3 py-1 rounded-full bg-amber-200 text-amber-900 flex items-center space-x-1.5">
                            <i class="fa-solid fa-hourglass-half"></i>
                            <span>Time Extension Pending Doctor Approval</span>
                        </span>
                        <span class="text-xs text-amber-700 font-medium">{{ $consultation->pendingTimeExtension->created_at->diffForHumans() }}</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mt-1">
                        Requested +{{ $consultation->pendingTimeExtension->minutes }} Minutes Extension ({{ $consultation->pendingTimeExtension->credits_cost }} credits)
                    </h3>
                    <p class="text-xs text-slate-600">
                        Waiting for Dr. {{ $consultation->vet->name }} to review and approve. Your credits will only be deducted once approved by the doctor.
                    </p>
                </div>
                <div>
                    <form method="POST" action="{{ route('consultation.extensions.cancel', [$consultation, $consultation->pendingTimeExtension]) }}" onsubmit="return confirm('Cancel this time extension request?');">
                        @csrf
                        <button type="submit" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition-all">
                            Cancel Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Doctor Scheduled Follow-Up Checkup Banner (CRITICAL USER INTERACTION) -->
    @if($consultation->is_follow_up && $consultation->status === 'pending')
        <div class="bg-gradient-to-r from-teal-900 via-slate-900 to-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-teal-500/40 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div class="flex items-center space-x-3.5">
                    <div class="w-14 h-14 rounded-2xl bg-teal-500/20 border border-teal-400/30 text-teal-300 flex items-center justify-center text-2xl shrink-0 shadow-lg shadow-teal-500/20">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-extrabold tracking-wider px-2.5 py-0.5 rounded-full bg-teal-500/20 text-teal-300 border border-teal-400/30">Action Required</span>
                        <h2 class="text-xl font-bold text-white mt-1">Dr. {{ $consultation->vet->name }} Scheduled a Follow-Up Checkup</h2>
                        <p class="text-xs text-slate-300">A follow-up consultation has been scheduled for <strong class="text-teal-200">{{ $consultation->all_pets->pluck('name')->join(', ') }}</strong>.</p>
                    </div>
                </div>
                @if($consultation->parentConsultation)
                    <a href="{{ route('client.bookings.show', $consultation->parentConsultation) }}" class="inline-flex items-center space-x-1.5 text-xs text-teal-300 hover:text-teal-200 bg-white/10 hover:bg-white/15 px-3.5 py-2 rounded-xl border border-white/10 transition-colors shrink-0">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i>
                        <span>Previous Session #{{ $consultation->parentConsultation->consultation_number }}</span>
                    </a>
                @endif
            </div>

            <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-700/50">
                        <span class="text-slate-400 block mb-1 font-semibold flex items-center space-x-1">
                            <i class="fa-solid fa-calendar-day text-teal-400"></i>
                            <span>Scheduled Checkup Date & Time:</span>
                        </span>
                        <strong class="text-emerald-400 font-mono text-base block">{{ $consultation->scheduled_at->format('F d, Y @ g:i A') }}</strong>
                        <span class="text-[11px] text-amber-300/90 mt-1 flex items-center space-x-1">
                            <i class="fa-solid fa-lock text-[10px]"></i>
                            <span>Clinically fixed by doctor (not editable by client)</span>
                        </span>
                    </div>
                    <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-700/50">
                        <span class="text-slate-400 block mb-1 font-semibold flex items-center space-x-1">
                            <i class="fa-solid fa-tag text-teal-400"></i>
                            <span>Follow-up Consultation Fee:</span>
                        </span>
                        @if(($consultation->credits_cost ?? 0) > 0)
                            <div class="flex items-baseline space-x-2">
                                <strong class="text-emerald-400 font-mono text-base">{{ $consultation->credits_cost }} credits</strong>
                                <span class="text-slate-400 text-xs">(₱{{ number_format($consultation->fee, 2) }})</span>
                            </div>
                            <span class="text-[11px] text-slate-300 mt-1 block">
                                Your balance: <strong class="{{ $userCredits >= $consultation->credits_cost ? 'text-emerald-300' : 'text-rose-300' }}">{{ number_format($userCredits) }} credits</strong>
                            </span>
                        @else
                            <strong class="text-emerald-400 font-mono text-base flex items-center space-x-1.5">
                                <i class="fa-solid fa-gift text-sm"></i>
                                <span>Complimentary (FREE • 0 Credits)</span>
                            </strong>
                            <span class="text-[11px] text-emerald-300 mt-1 block">Dr. {{ $consultation->vet->name }} has provided this follow-up at no charge.</span>
                        @endif
                    </div>
                </div>

                @if(($consultation->credits_cost ?? 0) > 0 && $userCredits < $consultation->credits_cost)
                    <div class="bg-rose-950/60 border border-rose-500/40 text-rose-200 p-3.5 rounded-xl text-xs flex items-center justify-between gap-3">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-triangle-exclamation text-rose-400 text-base shrink-0"></i>
                            <span>Insufficient credits! You need <strong>{{ $consultation->credits_cost }} credits</strong> to approve this follow-up. Please contact admin to top up your credits.</span>
                        </div>
                    </div>
                @endif

                <div class="border-t border-slate-700/60 pt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <p class="text-slate-300">
                        <i class="fa-solid fa-circle-info text-teal-400 mr-1"></i>
                        @if(($consultation->credits_cost ?? 0) > 0)
                            Credits will only be deducted from your account balance once you click <strong>Approve Checkup</strong>.
                        @else
                            Clicking <strong>Approve Checkup</strong> will immediately confirm this session at no cost to you.
                        @endif
                    </p>

                    <div class="flex items-center space-x-3 shrink-0">
                        <!-- Decline Button -->
                        <form method="POST" action="{{ route('client.bookings.decline-follow-up', $consultation) }}" onsubmit="return confirm('Decline this follow-up checkup?');">
                            @csrf
                            <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold transition-all">
                                Decline
                            </button>
                        </form>

                        <!-- Accept Button -->
                        <form method="POST" action="{{ route('client.bookings.accept-follow-up', $consultation) }}" onsubmit="return confirm('Approve and confirm this follow-up checkup? {{ ($consultation->credits_cost ?? 0) > 0 ? $consultation->credits_cost . \" credits will be deducted.\" : \"No credits will be deducted (Free).\" }}');">
                            @csrf
                            <button type="submit"
                                    @if(($consultation->credits_cost ?? 0) > 0 && $userCredits < $consultation->credits_cost) disabled @endif
                                    class="px-5 py-2.5 rounded-xl bg-teal-500 hover:bg-teal-600 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-extrabold shadow-lg shadow-teal-500/30 transition-all flex items-center space-x-1.5">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Approve & Confirm Checkup {{ ($consultation->credits_cost ?? 0) > 0 ? '(' . $consultation->credits_cost . ' Credits)' : '(Free)' }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
            <span class="font-mono font-black text-emerald-700 bg-emerald-100 px-3 py-1 rounded-xl text-sm">-{{ $consultation->credits_deducted }} credits</span>
        </div>
    @endif

    <!-- Linked Parent Consultation Banner -->
    @if($consultation->parentConsultation)
        <div class="bg-teal-50/70 border border-teal-200/80 rounded-3xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs shadow-2xs">
            <div class="flex items-center space-x-3.5">
                <div class="w-10 h-10 rounded-2xl bg-teal-600 text-white flex items-center justify-center text-lg font-bold shrink-0 shadow-sm shadow-teal-600/20">
                    <i class="fa-solid fa-link"></i>
                </div>
                <div>
                    <span class="text-teal-800 font-extrabold block text-[10px] uppercase tracking-wider">Follow-up For Previous Consultation</span>
                    <strong class="text-slate-900 text-sm">Consultation #{{ $consultation->parentConsultation->consultation_number }}</strong>
                    <span class="text-slate-500">({{ $consultation->parentConsultation->scheduled_at ? $consultation->parentConsultation->scheduled_at->format('M d, Y @ g:i A') : '' }})</span>
                </div>
            </div>
            <a href="{{ route('client.bookings.show', $consultation->parentConsultation) }}" class="bg-white hover:bg-slate-50 text-teal-800 font-bold text-xs px-4 py-2.5 rounded-xl border border-teal-200 shadow-2xs inline-flex items-center space-x-1.5 transition-all shrink-0">
                <span>View Previous Session & Prescription</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
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

            <!-- Medical Record & Prescription (if completed) -->
            @if($consultation->record)
                <div class="bg-emerald-50/50 border border-emerald-200/80 rounded-3xl p-6 sm:p-8 shadow-sm space-y-5">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center space-x-2.5 text-emerald-800">
                            <i class="fa-solid fa-clipboard-check text-emerald-600 text-xl"></i>
                            <div>
                                <h3 class="font-extrabold text-base text-slate-900">Veterinary Clinical Record & Prescription</h3>
                                <p class="text-[11px] text-slate-500">Issued by Dr. {{ $consultation->vet->name }} (PRC: {{ $consultation->vet->vetProfile->license_number ?? 'PRC-VET' }})</p>
                            </div>
                        </div>

                        @if(!empty($consultation->record->medication_info))
                            <a href="{{ route('consultation.prescription.show', $consultation) }}" target="_blank"
                               class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs px-4 py-2 rounded-xl shadow-md shadow-emerald-600/20 flex items-center space-x-1.5 transition-all">
                                <i class="fa-solid fa-file-prescription"></i>
                                <span>View & Print Official Rx</span>
                            </a>
                        @endif
                    </div>

                    <div class="space-y-3.5 text-xs text-slate-700">
                        @if($consultation->record->symptoms)
                            <div class="bg-white/80 p-3.5 rounded-2xl border border-slate-200/60">
                                <strong class="text-slate-900 block mb-0.5">Symptoms Observed:</strong>
                                <p class="text-slate-700 leading-relaxed">{{ $consultation->record->symptoms }}</p>
                            </div>
                        @endif
                        @if($consultation->record->assessment)
                            <div class="bg-white/80 p-3.5 rounded-2xl border border-slate-200/60">
                                <strong class="text-slate-900 block mb-0.5">Clinical Diagnosis / Assessment:</strong>
                                <p class="text-slate-700 leading-relaxed">{{ $consultation->record->assessment }}</p>
                            </div>
                        @endif
                        @if($consultation->record->recommendations)
                            <div class="bg-white/80 p-3.5 rounded-2xl border border-slate-200/60">
                                <strong class="text-slate-900 block mb-0.5">Recommendations & Care Instructions:</strong>
                                <p class="text-slate-700 leading-relaxed">{{ $consultation->record->recommendations }}</p>
                            </div>
                        @endif
                        @if($consultation->record->medication_info)
                            <div class="bg-white p-5 rounded-2xl border-2 border-emerald-300 shadow-sm space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2 text-emerald-900 font-extrabold text-sm">
                                        <span class="text-2xl font-serif font-black italic text-emerald-700">℞</span>
                                        <span>Official Prescription Medications</span>
                                    </div>
                                    <a href="{{ route('consultation.prescription.show', $consultation) }}" target="_blank"
                                       class="text-emerald-700 hover:text-emerald-800 text-xs font-bold underline flex items-center space-x-1">
                                        <i class="fa-solid fa-print"></i>
                                        <span>Print Rx</span>
                                    </a>
                                </div>
                                <div class="bg-emerald-50/50 p-3.5 rounded-xl border border-emerald-100 text-slate-900 font-medium whitespace-pre-line leading-relaxed text-xs">
{{ $consultation->record->medication_info }}
                                </div>
                            </div>
                        @endif
                        @if($consultation->record->follow_up_instructions)
                            <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 text-slate-700">
                                <strong class="text-slate-900 block mb-0.5">Follow-up Instructions:</strong>
                                <p class="leading-relaxed">{{ $consultation->record->follow_up_instructions }}</p>
                            </div>
                        @endif
                        @if($consultation->record->followUpConsultation)
                            <div class="bg-gradient-to-r from-teal-50 to-emerald-50 border border-teal-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-teal-600 text-white flex items-center justify-center text-sm font-bold shrink-0">
                                        <i class="fa-solid fa-calendar-check"></i>
                                    </div>
                                    <div>
                                        <span class="text-teal-800 font-extrabold block text-[10px] uppercase tracking-wider">Follow-up Teleconsultation Booked</span>
                                        <strong class="text-slate-900 text-xs">Session #{{ $consultation->record->followUpConsultation->consultation_number }}</strong>
                                        <span class="text-slate-600">• {{ $consultation->record->followUpConsultation->scheduled_at ? $consultation->record->followUpConsultation->scheduled_at->format('M d, Y @ g:i A') : '' }}</span>
                                        <span class="px-2 py-0.5 text-[10px] rounded-full font-bold ml-1
                                            @if($consultation->record->followUpConsultation->status === 'accepted') bg-emerald-100 text-emerald-800
                                            @elseif($consultation->record->followUpConsultation->status === 'pending') bg-amber-100 text-amber-800
                                            @else bg-slate-100 text-slate-700 @endif">
                                            {{ ucfirst(str_replace('_', ' ', $consultation->record->followUpConsultation->status)) }}
                                        </span>
                                    </div>
                                </div>
                                <a href="{{ route('client.bookings.show', $consultation->record->followUpConsultation) }}" class="bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition-colors inline-flex items-center space-x-1 shrink-0">
                                    <span>View Follow-up Session</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
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
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Booked Pet Patient(s)</h3>
                    <span class="text-[10px] font-bold bg-brand-50 text-brand-700 px-2.5 py-0.5 rounded-full border border-brand-200">
                        {{ $consultation->all_pets->count() }} {{ Str::plural('Pet', $consultation->all_pets->count()) }}
                    </span>
                </div>
                
                <div class="space-y-2.5 pt-1">
                    @foreach($consultation->all_pets as $pet)
                        <div class="flex items-center space-x-3 p-3 rounded-2xl bg-slate-50 border border-slate-100">
                            <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 font-bold flex items-center justify-center border border-brand-100 text-sm shrink-0 overflow-hidden">
                                @if($pet->photo)
                                    <img src="{{ asset('storage/' . $pet->photo) }}" class="w-full h-full object-cover">
                                @else
                                    <i class="fa-solid {{ $pet->animalType ? $pet->animalType->icon : 'fa-paw' }}"></i>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center space-x-1.5">
                                    <h4 class="font-bold text-slate-800 text-xs truncate">{{ $pet->name }}</h4>
                                    @if($pet->id === $consultation->pet_id)
                                        <span class="text-[9px] bg-brand-600 text-white font-bold px-1.5 py-0.5 rounded">Primary</span>
                                    @else
                                        <span class="text-[9px] bg-slate-200 text-slate-700 font-medium px-1.5 py-0.5 rounded">Extra Pet</span>
                                    @endif
                                </div>
                                <p class="text-[10px] text-slate-500">{{ $pet->animalType->name ?? 'Pet' }} • {{ $pet->breed_name }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    <!-- Client Request Time Extension Modal -->
    <div x-show="showExtensionModal"
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         style="display: none;">
        <div @click.away="showExtensionModal = false"
             class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-100 text-left">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-hourglass-start"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm">Request More Time</h3>
                        <p class="text-[11px] text-slate-500">Requires credits & doctor approval</p>
                    </div>
                </div>
                <button type="button" @click="showExtensionModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- Current Balance Banner -->
            <div class="bg-slate-50 rounded-2xl p-3 border border-slate-200/80 flex items-center justify-between text-xs">
                <span class="text-slate-600">Your Credit Balance:</span>
                <span class="font-mono font-bold text-brand-700 bg-white px-2.5 py-1 rounded-lg border border-slate-200" x-text="userCredits + ' credits'"></span>
            </div>

            <form method="POST" action="{{ route('consultation.request-extension', $consultation) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="minutes" :value="selectedMinutes">

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700">Select Extension Package:</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <template x-for="pkg in extensionPackages" :key="pkg.minutes">
                            <button type="button" @click="selectedMinutes = pkg.minutes"
                                    :class="selectedMinutes === pkg.minutes ? 'border-brand-600 bg-brand-50 text-brand-800 ring-2 ring-brand-500/30' : 'border-slate-200 hover:bg-slate-50 text-slate-700'"
                                    class="p-3 border rounded-xl text-center transition-all">
                                <span class="block text-sm font-extrabold" x-text="'+' + pkg.minutes + ' mins'"></span>
                                <span class="text-[10px] text-slate-500 font-semibold" x-text="pkg.credits + ' credits'"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div x-show="userCredits < selectedCredits" class="text-[11px] text-rose-600 bg-rose-50 border border-rose-200 p-2.5 rounded-xl">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> You need <strong x-text="selectedCredits + ' credits'"></strong> but have <strong x-text="userCredits"></strong> credits.
                </div>

                <div class="pt-2 flex items-center space-x-2">
                    <button type="button" @click="showExtensionModal = false" class="w-1/3 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200 transition-colors">Cancel</button>
                    <button type="submit"
                            :disabled="userCredits < selectedCredits"
                            class="w-2/3 py-2.5 bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-md shadow-brand-600/30 transition-all flex items-center justify-center space-x-1">
                        <span>Send Request (<span x-text="selectedCredits + ' credits'"></span>)</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function clientBookingShowComponent() {
    return {
        consultationId: {{ $consultation->id }},
        status: '{{ $consultation->status }}',
        declineReason: @json($consultation->decline_reason ?? ''),
        vetName: @json($consultation->vet ? $consultation->vet->name : 'Doctor'),
        isDeclinedToast: false,
        isFetchingStatus: false,
        statusCheckUrl: '{{ route("client.bookings.status", $consultation) }}',

        showExtensionModal: false,
        selectedMinutes: {{ ($extensionPackages[0]['minutes'] ?? 10) }},
        userCredits: {{ $userCredits }},
        extensionPackages: {{ json_encode($extensionPackages) }},
        creditsPerMinute: {{ $creditsPerMinute }},

        get selectedCredits() {
            const pkg = this.extensionPackages.find(p => p.minutes === this.selectedMinutes);
            return pkg ? pkg.credits : (this.selectedMinutes * (this.creditsPerMinute || 5));
        },

        init() {
            this.setupEcho();

            // Dual-layer fallback: Poll every 5s while pending + on tab focus
            if (this.status === 'pending') {
                const interval = setInterval(async () => {
                    if (this.status !== 'pending') {
                        clearInterval(interval);
                        return;
                    }
                    await this.fetchStatus();
                }, 5000);

                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden && this.status === 'pending') {
                        this.fetchStatus();
                    }
                });
            }
        },

        setupEcho() {
            if (!window.Echo) {
                setTimeout(() => this.setupEcho(), 350);
                return;
            }

            try {
                // Subscribe to private-consultation.{id}
                const channel = window.Echo.private(`consultation.${this.consultationId}`);
                channel.listen('.consultation.declined', (event) => this.handleDeclined(event))
                       .listen('consultation.declined', (event) => this.handleDeclined(event))
                       .listen('.ConsultationDeclined', (event) => this.handleDeclined(event))
                       .listen('ConsultationDeclined', (event) => this.handleDeclined(event));

                // Also subscribe to client's private user channel as secondary channel
                const userChannel = window.Echo.private(`App.Models.User.{{ Auth::id() }}`);
                userChannel.listen('.consultation.declined', (event) => {
                    if (event && (event.consultation_id == this.consultationId || event.id == this.consultationId)) {
                        this.handleDeclined(event);
                    }
                }).listen('consultation.declined', (event) => {
                    if (event && (event.consultation_id == this.consultationId || event.id == this.consultationId)) {
                        this.handleDeclined(event);
                    }
                });
            } catch (err) {
                console.warn('Echo consultation channel subscription error:', err);
            }
        },

        async handleDeclined(event) {
            console.log('Consultation declined event received via Reverb:', event);
            if (event && event.decline_reason) {
                this.declineReason = event.decline_reason;
            }
            await this.fetchStatus();
            this.isDeclinedToast = true;
            this.playNotificationAlert();
        },

        async fetchStatus() {
            this.isFetchingStatus = true;
            try {
                const response = await fetch(this.statusCheckUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.status !== this.status) {
                        this.status = data.status;
                        if (data.decline_reason) {
                            this.declineReason = data.decline_reason;
                        }
                        if (data.status === 'declined') {
                            this.isDeclinedToast = true;
                            this.playNotificationAlert();
                        }
                    }
                }
            } catch (e) {
                console.error('Fetch status error:', e);
            } finally {
                this.isFetchingStatus = false;
            }
        },

        playNotificationAlert() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const now = ctx.currentTime;
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = 'sine';
                osc.frequency.setValueAtTime(440, now);
                osc.frequency.setValueAtTime(330, now + 0.15);
                gain.gain.setValueAtTime(0.2, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
                osc.start(now);
                osc.stop(now + 0.5);
            } catch (e) {}
        }
    };
}
</script>
@endpush
@endsection
