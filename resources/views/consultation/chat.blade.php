@extends('layouts.app')

@section('title', 'Live Consultation Chat #' . $consultation->consultation_number)

@section('content')
<div class="max-w-4xl mx-auto flex flex-col h-[calc(100vh-10rem)] min-h-[550px]" 
     x-data="chatComponent({{ $consultation->id }}, {{ $user->id }}, {{ $isVet ? 'true' : 'false' }}, {{ $timer['remaining_seconds'] }}, {{ $timer['consumed_seconds'] }}, {{ $timer['total_seconds'] }}, {{ $consultation->duration_minutes ?: 15 }}, {{ $creditsPerMinute }}, {{ $userCredits }}, {{ json_encode($extensionPackages) }}, {{ json_encode($formattedMessages ?? []) }})">

    <!-- Chat Room Header -->
    <div class="bg-navy-800 text-white rounded-t-2xl p-4 shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-3 shrink-0 border-b border-slate-700">
        <div class="flex items-center space-x-3 min-w-0">
            <div class="w-10 h-10 rounded-xl bg-brand-600 text-white flex items-center justify-center font-bold text-lg shrink-0">
                <i class="fa-solid fa-comments"></i>
            </div>
            <div class="min-w-0">
                <div class="flex items-center space-x-2">
                    <h1 class="font-bold text-base leading-tight text-white truncate">Consultation #{{ $consultation->consultation_number }}</h1>
                    <span class="text-[10px] font-mono text-brand-300 bg-brand-950/80 px-2 py-0.5 rounded border border-brand-800 hidden xs:inline">Live Chat</span>
                </div>
                <p class="text-xs text-slate-300 truncate">
                    Pets: <strong class="text-brand-300">{{ $consultation->all_pets->pluck('name')->join(', ') }}</strong> • 
                    {{ $isVet ? 'Client:' : 'Vet:' }} 
                    <strong class="text-slate-100">{{ $isVet ? $consultation->client->name : 'Dr. ' . $consultation->vet->name }}</strong>
                </p>
            </div>
        </div>

        <div class="flex items-center space-x-2 sm:space-x-2.5 shrink-0 justify-between sm:justify-end border-t sm:border-t-0 border-slate-700/60 pt-2 sm:pt-0 flex-wrap sm:flex-nowrap gap-y-2">
            <!-- Reverb WebSocket Connection Status Badge -->
            <div class="flex items-center space-x-1 px-2 py-1 rounded-xl text-[10px] font-bold shrink-0 transition-all border"
                 :class="isEchoConnected ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-slate-700/40 text-slate-400 border-slate-600'"
                 :title="isEchoConnected ? 'Connected via Laravel Reverb WebSockets' : 'Connecting to Reverb WebSockets...'">
                <i class="fa-solid fa-bolt text-[9px]" :class="isEchoConnected ? 'text-amber-300 animate-pulse' : 'text-slate-400'"></i>
                <span x-text="isEchoConnected ? 'Reverb' : 'Connecting'"></span>
            </div>

            <!-- Doctor Presence Status Badge -->
            <div class="flex items-center space-x-1.5 px-2.5 py-1.5 rounded-xl text-xs font-bold shrink-0 transition-all"
                 :class="doctorPresent ? (isTimerRunning ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-brand-500/20 text-brand-300 border border-brand-500/30') : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'">
                <span class="w-2 h-2 rounded-full shrink-0" :class="doctorPresent ? 'bg-emerald-400 animate-ping' : 'bg-amber-400 animate-pulse'"></span>
                <span class="hidden xs:inline" x-text="doctorPresent ? (isTimerRunning ? (isVet && !clientPresent ? 'Deducting (Client away)' : 'Doctor Active') : 'Doctor Present') : 'Waiting for Doctor (Paused)'"></span>
                <span class="xs:hidden" x-text="doctorPresent ? 'Active' : 'Paused'"></span>
            </div>

            <!-- Countdown Timer Badge -->
            <div class="px-2.5 sm:px-3 py-1.5 rounded-xl border text-xs font-mono font-bold flex items-center space-x-1.5 transition-all shadow-sm shrink-0"
                 :class="isExpired ? 'bg-slate-900 border-slate-700 text-slate-400' : (remainingSeconds <= 15 ? (remainingSeconds > 0 ? 'bg-rose-500/20 border-rose-500 text-rose-300 ring-2 ring-rose-500/50 animate-pulse' : 'bg-rose-900/60 border-rose-800 text-rose-300') : (isTimerRunning ? 'bg-slate-900 border-slate-700 text-brand-400' : 'bg-slate-900 border-amber-700/60 text-amber-400'))">
                <i class="fa-solid fa-hourglass-half text-xs shrink-0" :class="isExpired ? 'text-slate-500' : (remainingSeconds <= 15 && remainingSeconds > 0 ? 'text-rose-400 animate-spin' : (isTimerRunning ? 'text-brand-500' : 'text-amber-500'))"></i>
                <div class="text-left leading-tight">
                    <div class="flex items-baseline space-x-1">
                        <span x-text="formattedRemaining" class="text-xs sm:text-sm font-black">00:00</span>
                        <span class="text-[9px] text-slate-400 font-sans hidden sm:inline" x-text="'/ ' + timeLimitMinutes + 'm'"></span>
                    </div>
                    <span class="text-[8px] uppercase font-sans block font-semibold"
                          :class="isExpired ? 'text-slate-400' : (remainingSeconds <= 15 ? 'text-rose-400' : (isTimerRunning ? 'text-emerald-400' : 'text-amber-400'))"
                          x-text="isExpired ? (remainingSeconds <= 0 ? 'Expired' : 'Ended') : (remainingSeconds <= 15 ? 'Ending!' : (isTimerRunning ? 'Deducting' : 'Timer Paused'))"></span>
                </div>
            </div>

            <!-- Doctor Add Free Time Button -->
            @if($isVet)
                <button type="button" @click="showAddFreeTimeModal = true"
                        class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-2.5 sm:px-3 py-2 rounded-xl transition-all flex items-center space-x-1 shadow-sm shrink-0"
                        title="Add complimentary consultation time (free to client)">
                    <i class="fa-solid fa-gift text-xs"></i>
                    <span class="hidden xs:inline">+ Free Time</span>
                    <span class="xs:hidden">+Time</span>
                </button>
            @else
                <!-- Client Request Extension Button -->
                <button type="button" @click="showExtensionModal = true"
                        class="bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs px-2.5 sm:px-3 py-2 rounded-xl transition-all flex items-center space-x-1 shadow-sm shrink-0"
                        title="Request time extension with credits">
                    <i class="fa-solid fa-hourglass-start text-xs"></i>
                    <span class="hidden xs:inline">Add Time</span>
                    <span class="xs:hidden">+Time</span>
                </button>
            @endif

            @if($consultation->type === 'video' || in_array($consultation->status, ['accepted', 'in_progress']))
                <a href="{{ route('consultation.video', $consultation) }}" class="bg-brand-500 hover:bg-brand-400 text-white font-bold text-xs px-3 py-2 rounded-xl transition-all flex items-center space-x-1.5 shadow-md shrink-0">
                    <i class="fa-solid fa-video"></i>
                    <span class="hidden sm:inline">Video Call</span>
                </a>
            @endif
            @if(auth()->user()->isVet())
                <a href="{{ route('vet.records.create', $consultation) }}" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-3 py-2 rounded-xl transition-all flex items-center space-x-1.5 shrink-0" title="Issue digital prescription & clinical record">
                    <i class="fa-solid fa-file-prescription"></i>
                    <span class="hidden sm:inline">Prescription</span>
                </a>
            @endif

            @if($consultation->record && !empty($consultation->record->medication_info))
                <a href="{{ route('consultation.prescription.show', $consultation) }}" target="_blank"
                   class="bg-emerald-700 hover:bg-emerald-600 text-white font-bold text-xs px-3 py-2 rounded-xl transition-all flex items-center space-x-1.5 shadow-sm shrink-0" title="View / Print Official Rx Slip">
                    <i class="fa-solid fa-print"></i>
                    <span class="hidden sm:inline">Rx Slip</span>
                </a>
            @endif

            @if(!in_array($consultation->status, ['completed', 'cancelled_by_client', 'cancelled_by_vet', 'declined']))
                <!-- End Chat Button -->
                @if(auth()->user()->isVet())
                    <button type="button" @click="showEndChatModal = true" x-show="!isExpired"
                            class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs px-2.5 sm:px-3 py-2 rounded-xl transition-all flex items-center space-x-1.5 shadow-sm shrink-0"
                            title="End chat consultation (mark completed)">
                        <i class="fa-solid fa-comment-slash text-xs"></i>
                        <span class="hidden xs:inline">End Chat</span>
                        <span class="xs:hidden">End</span>
                    </button>
                @else
                    <form id="endChatFormClient" method="POST" action="{{ route('consultation.chat.end', $consultation) }}" class="m-0 p-0 flex items-center shrink-0" x-show="!isExpired">
                        @csrf
                        <button type="submit" onclick="return confirm('End this chat consultation? This will mark the teleconsultation as completed.');"
                                class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs px-2.5 sm:px-3 py-2 rounded-xl transition-all flex items-center space-x-1.5 shadow-sm shrink-0"
                                title="End chat consultation (mark completed)">
                            <i class="fa-solid fa-comment-slash text-xs"></i>
                            <span class="hidden xs:inline">End Chat</span>
                            <span class="xs:hidden">End</span>
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    <!-- Vet Pending Extension Approval Banner -->
    <div x-show="pendingExtension && isVet"
         x-transition
         class="bg-amber-500 text-slate-950 px-4 py-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs font-bold shadow-md border-b border-amber-400 animate-pulse"
         style="display: none;">
        <div class="flex items-center space-x-2.5">
            <span class="w-8 h-8 rounded-xl bg-amber-950/20 flex items-center justify-center text-amber-950 text-sm">
                <i class="fa-solid fa-bell"></i>
            </span>
            <div>
                <span class="text-xs font-extrabold text-amber-950 block">Time Extension Requested by Client!</span>
                <span class="text-[11px] font-medium text-amber-950">Client requested +<span x-text="pendingExtension?.minutes" class="font-extrabold"></span> minutes (<span x-text="pendingExtension?.credits_cost" class="font-extrabold"></span> credits). Do you accept?</span>
            </div>
        </div>
        <div class="flex items-center space-x-2 shrink-0 self-end sm:self-auto">
            <button type="button" @click="approveExtension(pendingExtension.id)" :disabled="isExtending"
                    class="bg-emerald-800 hover:bg-emerald-900 text-white px-4 py-2 rounded-xl text-xs font-extrabold shadow transition-all flex items-center space-x-1">
                <i class="fa-solid fa-check"></i>
                <span>Approve (+<span x-text="pendingExtension?.minutes"></span>m)</span>
            </button>
            <button type="button" @click="declineExtension(pendingExtension.id)" :disabled="isExtending"
                    class="bg-slate-900 hover:bg-slate-800 text-white px-3 py-2 rounded-xl text-xs font-semibold transition-all">
                Decline
            </button>
        </div>
    </div>

    <!-- Client Pending Extension Waiting Banner -->
    <div x-show="pendingExtension && !isVet"
         x-transition
         class="bg-slate-900 border-b border-brand-500 text-white px-4 py-3 flex items-center justify-between gap-3 text-xs font-medium shadow-md"
         style="display: none;">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-hourglass-half text-brand-400 animate-spin"></i>
            <span>Extension request for +<strong x-text="pendingExtension?.minutes" class="text-brand-300"></strong> minutes (<span x-text="pendingExtension?.credits_cost"></span> credits) is awaiting Dr. approval...</span>
        </div>
        <button type="button" @click="cancelExtension(pendingExtension.id)"
                class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs px-3 py-1.5 rounded-lg border border-slate-700 transition-colors">
            Cancel
        </button>
    </div>

    <!-- Time Extended Notification Banner -->
    <div x-show="showTimeExtendedBanner"
         x-transition
         class="bg-emerald-600 text-white px-4 py-3 flex items-center justify-between gap-3 text-xs font-bold shadow-md border-b border-emerald-500"
         style="display: none;">
        <div class="flex items-center space-x-2.5">
            <span class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center text-white text-sm">
                <i class="fa-solid fa-clock"></i>
            </span>
            <span x-text="timeExtendedMessage"></span>
        </div>
        <button type="button" @click="showTimeExtendedBanner = false" class="text-white hover:text-emerald-200">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>

    <!-- 15-Second Warning Alert Banner -->
    <div x-show="remainingSeconds <= 15 && remainingSeconds > 0"
         x-transition
         class="bg-rose-600/95 backdrop-blur text-white px-4 py-2.5 flex items-center justify-between gap-2 text-xs font-bold shadow-md animate-pulse border-b border-rose-500"
         style="display: none;">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-triangle-exclamation text-base shrink-0"></i>
            <span>Consultation time limit approaching! Chat room closes in <span x-text="remainingSeconds" class="font-mono underline font-black text-sm"></span> seconds.</span>
        </div>
        <div class="flex items-center space-x-2 self-end sm:self-auto shrink-0">
            @if($isVet)
                <button type="button" @click="showAddFreeTimeModal = true" class="bg-white text-rose-700 font-bold px-2.5 py-1 rounded-lg text-xs hover:bg-rose-50 shadow">
                    + Add Free Time
                </button>
            @else
                <button type="button" @click="showExtensionModal = true" class="bg-white text-rose-700 font-bold px-2.5 py-1 rounded-lg text-xs hover:bg-rose-50 shadow">
                    + Request Extension
                </button>
            @endif
        </div>
    </div>

    <!-- Expired Alert Banner -->
    <div x-show="isExpired"
         x-transition
         class="bg-slate-900 text-white px-4 py-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs font-semibold shadow-md border-b border-slate-700"
         style="display: none;">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-circle-stop text-rose-500 text-base shrink-0"></i>
            <span x-text="remainingSeconds <= 0 ? ('Consultation time limit reached. All ' + timeLimitMinutes + ' minutes have been consumed. Chat is in read-only mode.') : 'Consultation completed. Chat is in read-only mode.'"></span>
        </div>
        <div class="flex items-center space-x-2 self-end sm:self-auto flex-wrap gap-y-1">
            @if(auth()->user()->isVet())
                <button type="button" @click="showAddFreeTimeModal = true" class="bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-colors mr-1">
                    + Add Free Time to Reopen
                </button>
                @if($consultation->record && !empty($consultation->record->medication_info))
                    <a href="{{ route('consultation.prescription.show', $consultation) }}" target="_blank"
                       class="bg-emerald-700 hover:bg-emerald-600 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-colors flex items-center space-x-1 mr-1">
                        <i class="fa-solid fa-print"></i>
                        <span>View Rx Slip</span>
                    </a>
                @endif
                <a href="{{ route('vet.records.create', $consultation) }}" class="bg-teal-600 hover:bg-teal-500 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-colors flex items-center space-x-1">
                    <i class="fa-solid fa-file-prescription"></i>
                    <span>{{ ($consultation->record && !empty($consultation->record->medication_info)) ? 'Edit Prescription & Record' : 'Create Prescription & Record' }}</span>
                </a>
            @else
                <button type="button" @click="showExtensionModal = true" class="bg-amber-600 hover:bg-amber-500 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-colors mr-1">
                    + Request Extension
                </button>
                @if($consultation->record && !empty($consultation->record->medication_info))
                    <a href="{{ route('consultation.prescription.show', $consultation) }}" target="_blank"
                       class="bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-colors flex items-center space-x-1 mr-1">
                        <i class="fa-solid fa-file-prescription"></i>
                        <span>View Prescription (Rx)</span>
                    </a>
                @endif
                <a href="{{ route('client.bookings.show', $consultation) }}" class="bg-brand-600 hover:bg-brand-500 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-colors">View Booking Summary</a>
            @endif
        </div>
    </div>

    <!-- Doctor Add Free Time Modal -->
    <div x-show="showAddFreeTimeModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;" x-transition>
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 space-y-4 shadow-2xl border border-slate-200" @click.outside="showAddFreeTimeModal = false">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-base font-bold">
                        <i class="fa-solid fa-gift"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm">Add Complimentary Time</h3>
                        <p class="text-[11px] text-emerald-600 font-semibold">Free of charge to client (0 credits)</p>
                    </div>
                </div>
                <button type="button" @click="showAddFreeTimeModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed">
                Add extra consultation minutes. This time is completely free for your client and immediately extends the consultation countdown.
            </p>
            <div class="grid grid-cols-3 gap-2">
                <button type="button" @click="doctorAddTime(5)" :disabled="isExtending" class="py-2.5 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-400 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 hover:text-emerald-700 transition-all">+5 mins</button>
                <button type="button" @click="doctorAddTime(10)" :disabled="isExtending" class="py-2.5 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-400 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 hover:text-emerald-700 transition-all">+10 mins</button>
                <button type="button" @click="doctorAddTime(15)" :disabled="isExtending" class="py-2.5 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-400 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 hover:text-emerald-700 transition-all">+15 mins</button>
            </div>
            <div class="pt-2">
                <button type="button" @click="showAddFreeTimeModal = false" class="w-full py-2 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200 transition-colors">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Client Request Time Extension Modal -->
    <div x-show="showExtensionModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;" x-transition>
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200" @click.outside="showExtensionModal = false">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center text-base font-bold">
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
                <button type="button" @click="submitExtensionRequest()"
                        :disabled="isExtending || userCredits < selectedCredits"
                        class="w-2/3 py-2.5 bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-md shadow-brand-600/30 transition-all flex items-center justify-center space-x-1">
                    <span>Send Request (<span x-text="selectedCredits + ' credits'"></span>)</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Doctor End Chat Confirmation & Prescription Options Modal -->
    @if(auth()->user()->isVet())
    <div x-show="showEndChatModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;" x-transition>
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200" @click.outside="showEndChatModal = false">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-base font-bold">
                        <i class="fa-solid fa-comment-slash"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm sm:text-base">Conclude Chat Consultation</h3>
                        <p class="text-[11px] text-slate-500">Teleconsultation for {{ $consultation->pet->name }}</p>
                    </div>
                </div>
                <button type="button" @click="showEndChatModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                Ending this chat will mark the teleconsultation as completed. What would you like to do next?
            </p>

            <form id="endChatForm" method="POST" action="{{ route('consultation.chat.end', $consultation) }}" class="space-y-2">
                @csrf
                <input type="hidden" name="redirect_to" id="endChatRedirectTo" value="records">

                <button type="button" @click="submitEndChat('records')"
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs py-3 px-4 rounded-xl shadow-md shadow-emerald-600/20 flex items-center justify-center space-x-2 transition-all">
                    <i class="fa-solid fa-file-prescription text-sm"></i>
                    <span>End Chat & Create Prescription</span>
                </button>

                <button type="button" @click="submitEndChat('summary')"
                        class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-2.5 px-4 rounded-xl transition-all flex items-center justify-center space-x-1.5">
                    <i class="fa-solid fa-check"></i>
                    <span>End Chat Only (Go to Summary)</span>
                </button>

                <button type="button" @click="showEndChatModal = false"
                        class="w-full text-slate-400 hover:text-slate-600 text-xs py-2 font-semibold">
                    Cancel & Continue Chat
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- Messages Container -->
    <div class="flex-grow bg-white border-x border-slate-200 p-6 overflow-y-auto space-y-4 custom-scrollbar" id="messages-container">
        <template x-for="msg in messages" :key="msg.id">
            <div class="flex flex-col group relative" :class="msg.is_me ? 'items-end' : 'items-start'">
                <div class="flex items-center space-x-1.5 text-[11px] text-slate-400 mb-1">
                    <span class="font-semibold text-slate-600" x-text="msg.sender_name"></span>
                    <span>•</span>
                    <span x-text="msg.created_at"></span>

                    <!-- Appended Seen / Sent status to time sent by user -->
                    <template x-if="msg.is_me">
                        <span class="inline-flex items-center space-x-1">
                            <span>•</span>
                            <span x-show="msg.is_read" class="text-brand-600 font-bold inline-flex items-center space-x-1" :title="'Seen at ' + (msg.read_at || '')">
                                <i class="fa-solid fa-check-double text-[9px]"></i>
                                <span>Seen <span x-show="msg.read_at" x-text="msg.read_at"></span></span>
                            </span>
                            <span x-show="!msg.is_read" class="text-slate-400 inline-flex items-center space-x-1" title="Delivered">
                                <i class="fa-solid fa-check text-[9px]"></i>
                                <span>Sent</span>
                            </span>
                        </span>
                    </template>
                </div>

                <div class="max-w-[85%] sm:max-w-[75%] rounded-2xl text-xs shadow-sm cursor-default"
                     :class="[
                         msg.is_me ? 'bg-brand-600 text-white rounded-tr-none' : 'bg-slate-100 text-slate-800 rounded-tl-none border border-slate-200/60',
                         msg.message ? 'p-3.5 sm:p-4' : 'p-2'
                     ]"
                     :title="msg.is_me ? (msg.is_read ? 'Seen at ' + (msg.read_at || msg.created_at) : 'Sent at ' + msg.created_at) : ''">
                    <p x-text="msg.message" class="whitespace-pre-wrap leading-relaxed" x-show="msg.message"></p>

                    <template x-if="msg.attachment_url">
                        <div :class="msg.message ? 'mt-2.5 pt-2 border-t' : ''"
                             :style="msg.message ? (msg.is_me ? 'border-color: rgba(255,255,255,0.25)' : 'border-color: rgba(203,213,225,0.8)') : ''">
                            <template x-if="msg.attachment_type === 'image'">
                                <div class="relative group/attachment inline-block max-w-full">
                                    <a :href="msg.attachment_url" target="_blank" rel="noopener noreferrer"
                                       @click.prevent="openMediaPreview(msg.attachment_url, 'image')"
                                       class="block relative rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all cursor-pointer bg-black/10">
                                        <img :src="msg.attachment_url"
                                             class="rounded-xl max-h-60 max-w-full object-cover transition-transform duration-200 group-hover/attachment:scale-[1.02]"
                                             alt="Attachment image"
                                             loading="lazy">
                                        <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover/attachment:opacity-100 transition-opacity flex items-center justify-center space-x-1.5 text-white text-xs font-bold pointer-events-none">
                                            <i class="fa-solid fa-magnifying-glass-plus text-sm"></i>
                                            <span>Click to preview</span>
                                        </div>
                                    </a>
                                </div>
                            </template>
                            <template x-if="msg.attachment_type === 'video'">
                                <div class="rounded-2xl overflow-hidden shadow-md bg-black max-w-sm sm:max-w-md w-full border border-black/20 my-0.5">
                                    <div class="relative bg-black flex items-center justify-center min-h-[140px]">
                                        <video :src="msg.attachment_url"
                                               controls
                                               playsinline
                                               preload="metadata"
                                               class="w-full max-h-72 sm:max-h-80 rounded-t-xl bg-black object-contain focus:outline-none">
                                            Your browser does not support the video tag.
                                        </video>
                                    </div>
                                    <div class="px-3.5 py-2.5 bg-slate-900/95 flex items-center justify-between text-[11px] text-white/90 rounded-b-xl border-t border-white/10">
                                        <span class="inline-flex items-center space-x-1.5 font-medium">
                                            <i class="fa-solid fa-circle-play text-xs text-brand-400"></i>
                                            <span>Video Clip</span>
                                        </span>
                                        <div class="flex items-center space-x-1.5">
                                            <button type="button"
                                                    @click="openMediaPreview(msg.attachment_url, 'video')"
                                                    class="hover:text-white inline-flex items-center space-x-1 font-semibold text-[10px] bg-white/15 hover:bg-white/25 px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                                                    title="Watch expanded theater preview">
                                                <i class="fa-solid fa-expand text-[9px]"></i>
                                                <span>Expand</span>
                                            </button>
                                            <a :href="msg.attachment_url" target="_blank" download
                                               class="hover:text-white inline-flex items-center space-x-1 font-semibold text-[10px] bg-white/15 hover:bg-white/25 px-2.5 py-1 rounded-lg transition-colors"
                                               title="Download video">
                                                <i class="fa-solid fa-download text-[9px]"></i>
                                                <span class="hidden xs:inline">Download</span>
                                            </a>
                                            <a :href="msg.attachment_url" target="_blank" rel="noopener noreferrer"
                                               class="hover:text-white inline-flex items-center space-x-1 font-semibold text-[10px] bg-white/15 hover:bg-white/25 px-2 py-1 rounded-lg transition-colors"
                                               title="Open in new tab">
                                                <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template x-if="msg.attachment_type !== 'image' && msg.attachment_type !== 'video'">
                                <a :href="msg.attachment_url" target="_blank" download
                                   class="inline-flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-colors"
                                   :class="msg.is_me ? 'bg-white/20 text-white hover:bg-white/30' : 'bg-white text-brand-700 hover:bg-slate-50 border border-slate-200 shadow-sm'">
                                    <i class="fa-solid fa-file-arrow-down text-sm"></i>
                                    <span>Download Attachment</span>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- Message Input Bar -->
    <div class="bg-slate-50 border-x border-b border-slate-200 rounded-b-2xl p-4 shrink-0">
        <div x-show="isExpired" class="text-center py-2 text-xs text-slate-500 font-medium flex items-center justify-center flex-wrap gap-2">
            <div class="flex items-center">
                <i class="fa-solid fa-lock text-slate-400 mr-1.5"></i>
                <span x-text="remainingSeconds <= 0 ? 'Consultation time expired. Chat messaging is now closed.' : 'Consultation has ended. Chat messaging is now closed.'"></span>
            </div>
            @if(!$isVet)
                <button type="button" @click="showExtensionModal = true" class="font-bold text-brand-600 hover:text-brand-800 underline inline-flex items-center space-x-1">
                    <i class="fa-solid fa-hourglass-start text-[10px]"></i>
                    <span>Request Extension (+Add Time)</span>
                </button>
            @else
                <button type="button" @click="showAddFreeTimeModal = true" class="font-bold text-emerald-600 hover:text-emerald-800 underline inline-flex items-center space-x-1">
                    <i class="fa-solid fa-gift text-[10px]"></i>
                    <span>Add Free Time</span>
                </button>
            @endif
        </div>
        <form @submit.prevent="sendMessage()" class="flex items-center space-x-3" x-show="!isExpired">
            <!-- File Upload Button -->
            <label class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center cursor-pointer hover:bg-slate-100 transition-colors shrink-0 shadow-sm"
                   :class="isExpired ? 'opacity-50 pointer-events-none' : ''"
                   title="Attach photo, video or document (up to 50MB)">
                <i class="fa-solid fa-paperclip text-sm"></i>
                <input type="file" id="chat-file" class="hidden"
                       accept="image/*,video/*,.mp4,.mov,.webm,.avi,.mkv,.ogv,.m4v,.3gp,.pdf,.doc,.docx"
                       @change="handleFileChange($event)" :disabled="isExpired">
            </label>

            <!-- Attachment File Preview Pill -->
            <div x-show="attachmentName" class="text-xs bg-brand-100 text-brand-800 px-3 py-1.5 rounded-xl border border-brand-200 flex items-center space-x-2 shrink-0">
                <i class="text-xs text-brand-600"
                   :class="attachmentIsVideo ? 'fa-solid fa-video' : (attachmentIsImage ? 'fa-solid fa-image' : 'fa-solid fa-file-lines')"></i>
                <span class="font-medium truncate max-w-[130px]" x-text="attachmentName"></span>
                <span class="text-[10px] text-brand-600/80 font-mono" x-text="attachmentFormattedSize" x-show="attachmentFormattedSize"></span>
                <button type="button" @click="clearFile()" class="text-brand-600 hover:text-rose-600 ml-0.5"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <input type="text" x-model="newMessage" placeholder="Type your message to the {{ auth()->user()->isVet() ? 'client' : 'veterinarian' }}..."
                   class="flex-grow rounded-xl border-slate-200 text-xs py-3 px-4 focus:ring-brand-500 focus:border-brand-500 shadow-sm"
                   :disabled="isSending || isExpired">

            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs px-5 py-3 rounded-xl shadow-md shadow-brand-600/30 transition-all flex items-center space-x-1.5 shrink-0"
                    :disabled="isSending || isExpired">
                <span x-text="isSending ? (attachmentFile ? 'Compressing & Sending...' : 'Sending...') : 'Send'"></span>
                <i :class="isSending ? 'fa-solid fa-spinner animate-spin text-xs' : 'fa-solid fa-paper-plane text-xs'"></i>
            </button>
        </form>
    </div>

    <!-- Media Lightbox Preview Modal (Image & Video) -->
    <div x-show="previewMediaUrl"
         x-transition.opacity
         class="fixed inset-0 z-50 bg-slate-950/95 backdrop-blur-md flex items-center justify-center p-4 select-none"
         @keydown.escape.window="closeMediaPreview()"
         style="display: none;">
        <!-- Top Toolbar -->
        <div class="absolute top-4 right-4 flex items-center space-x-2 z-10">
            <a :href="previewMediaUrl" target="_blank" download
               class="text-white/90 hover:text-white bg-slate-800/80 hover:bg-slate-800 px-3 py-2 rounded-xl shadow-lg transition-all text-xs font-semibold flex items-center space-x-1.5 border border-slate-700"
               :title="previewMediaType === 'video' ? 'Download video' : 'Download image'">
                <i class="fa-solid fa-download"></i>
                <span class="hidden sm:inline">Download</span>
            </a>
            <a :href="previewMediaUrl" target="_blank" rel="noopener noreferrer"
               class="text-white/90 hover:text-white bg-slate-800/80 hover:bg-slate-800 px-3 py-2 rounded-xl shadow-lg transition-all text-xs font-semibold flex items-center space-x-1.5 border border-slate-700"
               title="Open full size in new tab">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span class="hidden sm:inline">Open in Tab</span>
            </a>
            <button type="button" @click="closeMediaPreview()"
                    class="text-white/90 hover:text-white bg-slate-800/80 hover:bg-slate-800 w-9 h-9 rounded-full shadow-lg transition-all flex items-center justify-center border border-slate-700 cursor-pointer"
                    title="Close (Esc)">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Centered Lightbox Content -->
        <div class="max-w-5xl max-h-[85vh] w-full flex items-center justify-center p-2" @click.outside="closeMediaPreview()">
            <template x-if="previewMediaType === 'image'">
                <img :src="previewMediaUrl"
                     class="max-w-full max-h-[80vh] object-contain rounded-2xl shadow-2xl border border-white/10"
                     alt="Image preview">
            </template>
            <template x-if="previewMediaType === 'video'">
                <div class="w-full max-w-4xl bg-black rounded-2xl overflow-hidden shadow-2xl border border-white/10">
                    <video id="modal-video-player"
                           :src="previewMediaUrl"
                           controls
                           autoplay
                           playsinline
                           class="w-full max-h-[75vh] object-contain bg-black focus:outline-none">
                        Your browser does not support the video tag.
                    </video>
                </div>
            </template>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function chatComponent(consultationId, userId, isVet, initialRemaining, initialConsumed, totalDuration, timeLimitMinutes, creditsPerMinute, userCredits, extensionPackages, initialMessages) {
        return {
            consultationId: consultationId,
            userId: userId,
            isVet: isVet,
            messages: Array.isArray(initialMessages) ? initialMessages : [],
            isEchoConnected: false,
            newMessage: '',
            attachmentFile: null,
            attachmentName: '',
            isSending: false,
            previewMediaUrl: null,
            previewMediaType: null,
            previewImageUrl: null,
            attachmentFileSize: 0,

            get attachmentFormattedSize() {
                if (!this.attachmentFileSize) return '';
                const bytes = this.attachmentFileSize;
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return '(' + parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i] + ')';
            },

            get attachmentIsVideo() {
                if (!this.attachmentFile) return false;
                const ext = (this.attachmentFile.name.split('.').pop() || '').toLowerCase();
                const videoExts = ['mp4', 'mov', 'webm', 'avi', 'mkv', 'ogv', 'm4v', '3gp'];
                return (this.attachmentFile.type && this.attachmentFile.type.startsWith('video/')) || videoExts.includes(ext);
            },

            get attachmentIsImage() {
                if (!this.attachmentFile) return false;
                const ext = (this.attachmentFile.name.split('.').pop() || '').toLowerCase();
                const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                return (this.attachmentFile.type && this.attachmentFile.type.startsWith('image/')) || imageExts.includes(ext);
            },
            pollTimer: null,
            timerSyncInterval: null,
            tickerInterval: null,
            remainingSeconds: initialRemaining !== undefined ? initialRemaining : 900,
            consumedSeconds: initialConsumed || 0,
            totalDurationSeconds: totalDuration || 900,
            timeLimitMinutes: timeLimitMinutes || 15,
            creditsPerMinute: creditsPerMinute || 5,
            userCredits: userCredits || 0,
            extensionPackages: extensionPackages || [
                { minutes: 10, credits: 50 },
                { minutes: 15, credits: 75 },
                { minutes: 20, credits: 100 },
                { minutes: 25, credits: 125 },
                { minutes: 30, credits: 150 }
            ],
            doctorPresent: isVet ? true : false,
            clientPresent: !isVet ? true : false,
            isTimerRunning: (isVet && '{{ $consultation->status }}' !== 'completed' && initialRemaining > 0),
            isExpired: (initialRemaining !== undefined && initialRemaining <= 0) || '{{ $consultation->status }}' === 'completed',

            // End Chat & Extension Modals & State
            showEndChatModal: false,
            showAddFreeTimeModal: false,
            showExtensionModal: false,
            showTimeExtendedBanner: false,
            timeExtendedMessage: '',
            selectedMinutes: (extensionPackages && extensionPackages.length > 0) ? extensionPackages[0].minutes : 10,
            pendingExtension: null,
            isExtending: false,

            submitEndChat(redirectTo) {
                const form = document.getElementById('endChatForm');
                const input = document.getElementById('endChatRedirectTo');
                if (input) input.value = redirectTo;
                if (form) form.submit();
            },

            get selectedCredits() {
                const pkg = this.extensionPackages.find(p => p.minutes === this.selectedMinutes);
                return pkg ? pkg.credits : (this.selectedMinutes * (this.creditsPerMinute || 5));
            },

            get formattedRemaining() {
                const mins = Math.floor(this.remainingSeconds / 60).toString().padStart(2, '0');
                const secs = (this.remainingSeconds % 60).toString().padStart(2, '0');
                return `${mins}:${secs}`;
            },

            get formattedConsumed() {
                const mins = Math.floor(this.consumedSeconds / 60).toString().padStart(2, '0');
                const secs = (this.consumedSeconds % 60).toString().padStart(2, '0');
                return `${mins}:${secs}`;
            },

            init() {
                this.scrollToBottom();
                this.startTicker();
                this.initEcho();

                window.addEventListener('beforeunload', () => {
                    if (window.Echo) {
                        window.Echo.leave(`consultation.${this.consultationId}`);
                    }
                });

                // Heartbeat sync every 5 seconds for timer & presence (zero message querying overhead)
                this.timerSyncInterval = setInterval(() => {
                    this.syncTime();
                }, 5000);

                // Emergency fallback: only poll messages if Reverb WebSocket disconnects
                this.pollTimer = setInterval(() => {
                    if (!this.isEchoConnected) {
                        this.fetchMessages();
                    }
                }, 3500);
            },

            initEcho() {
                if (window.Echo) {
                    this.setupEchoListeners();
                } else {
                    let attempts = 0;
                    const interval = setInterval(() => {
                        attempts++;
                        if (window.Echo) {
                            clearInterval(interval);
                            this.setupEchoListeners();
                        } else if (attempts >= 10) {
                            clearInterval(interval);
                            console.warn('Echo not found after 10 attempts. Emergency fallback polling active.');
                        }
                    }, 300);
                }
            },

            setupEchoListeners() {
                if (!window.Echo) return;

                try {
                    const channel = window.Echo.private(`consultation.${this.consultationId}`);

                    channel.subscribed(() => {
                        this.isEchoConnected = true;
                    });

                    channel.error(() => {
                        this.isEchoConnected = false;
                    });

                    channel.listen('.message.sent', (e) => {
                        const exists = this.messages.some(m => m.id === e.id);
                        if (!exists) {
                            this.messages.push({
                                id: e.id,
                                sender_name: e.sender_name,
                                is_me: e.sender_id === this.userId,
                                message: e.message,
                                attachment_url: e.attachment_url,
                                attachment_type: e.attachment_type,
                                created_at: e.created_at,
                                is_read: e.is_read,
                                read_at: e.read_at
                            });
                            this.scrollToBottom();

                            // If consultation ended message, lock room
                            if (e.message && e.message.includes('Consultation ended by')) {
                                this.isExpired = true;
                                this.isTimerRunning = false;
                            }

                            // If message is from other user, send read receipt
                            if (e.sender_id !== this.userId) {
                                this.markAsRead();
                            }
                        }
                    })
                    .listen('.message.read', (e) => {
                        if (e.reader_id !== this.userId) {
                            this.messages.forEach(m => {
                                if (m.is_me) {
                                    m.is_read = true;
                                    if (e.read_at) {
                                        m.read_at = e.read_at;
                                    }
                                }
                            });
                        }
                    })
                    .listen('.time.updated', (e) => {
                        if (e.action === 'requested') {
                            this.pendingExtension = e.extension;
                        } else if (e.action === 'approved') {
                            this.pendingExtension = null;
                            if (e.timer) {
                                const newMinutes = e.timer.duration_minutes !== undefined 
                                    ? Number(e.timer.duration_minutes) 
                                    : (e.timer.total_seconds ? Math.round(Number(e.timer.total_seconds) / 60) : this.timeLimitMinutes);

                                if (newMinutes > this.timeLimitMinutes) {
                                    const added = newMinutes - this.timeLimitMinutes;
                                    this.timeLimitMinutes = newMinutes;
                                    this.timeExtendedMessage = `🎉 Consultation time extended by +${added} min! New limit: ${newMinutes}m`;
                                    this.showTimeExtendedBanner = true;
                                    setTimeout(() => { this.showTimeExtendedBanner = false; }, 6000);
                                } else if (newMinutes) {
                                    this.timeLimitMinutes = newMinutes;
                                }

                                if (e.timer.remaining_seconds !== undefined) {
                                    this.remainingSeconds = e.timer.remaining_seconds;
                                }
                                if (e.timer.total_seconds !== undefined) {
                                    this.totalDurationSeconds = e.timer.total_seconds;
                                }
                                this.isExpired = false;
                                if (this.doctorPresent) {
                                    this.isTimerRunning = true;
                                }
                            }
                            if (e.client_credits !== null && e.client_credits !== undefined) {
                                this.userCredits = e.client_credits;
                                window.dispatchEvent(new CustomEvent('credits-updated', { detail: { credits: e.client_credits } }));
                            }
                        } else if (e.action === 'free_time_added') {
                            if (e.timer) {
                                const newMinutes = e.timer.duration_minutes !== undefined 
                                    ? Number(e.timer.duration_minutes) 
                                    : (e.timer.total_seconds ? Math.round(Number(e.timer.total_seconds) / 60) : this.timeLimitMinutes);

                                if (newMinutes > this.timeLimitMinutes) {
                                    const added = newMinutes - this.timeLimitMinutes;
                                    this.timeLimitMinutes = newMinutes;
                                    this.timeExtendedMessage = `🎉 Dr. added +${added} min complimentary time!`;
                                    this.showTimeExtendedBanner = true;
                                    setTimeout(() => { this.showTimeExtendedBanner = false; }, 6000);
                                } else if (newMinutes) {
                                    this.timeLimitMinutes = newMinutes;
                                }

                                if (e.timer.remaining_seconds !== undefined) {
                                    this.remainingSeconds = e.timer.remaining_seconds;
                                }
                                if (e.timer.total_seconds !== undefined) {
                                    this.totalDurationSeconds = e.timer.total_seconds;
                                }
                                this.isExpired = false;
                                if (this.doctorPresent) {
                                    this.isTimerRunning = true;
                                }
                            }
                        } else if (e.action === 'declined' || e.action === 'cancelled') {
                            this.pendingExtension = null;
                        }
                    });
                } catch (err) {
                    this.isEchoConnected = false;
                    console.warn('Error subscribing to Echo private channel:', err);
                }
            },

            syncTime() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken) return;

                fetch(`/consultation/${this.consultationId}/sync-time`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success' && data.timer) {
                        this.remainingSeconds = data.timer.remaining_seconds;
                        this.consumedSeconds = data.timer.consumed_seconds;
                        this.totalDurationSeconds = data.timer.total_seconds;
                        this.doctorPresent = data.timer.doctor_present;
                        this.clientPresent = data.timer.client_present;
                        this.isTimerRunning = data.timer.is_timer_running && data.timer.consultation_status !== 'completed';
                        this.pendingExtension = data.timer.pending_extension;
                        this.isExpired = data.timer.is_expired || data.timer.consultation_status === 'completed' || this.remainingSeconds <= 0;

                        const newMinutes = data.timer.duration_minutes !== undefined 
                            ? Number(data.timer.duration_minutes) 
                            : (data.timer.total_seconds ? Math.round(Number(data.timer.total_seconds) / 60) : this.timeLimitMinutes);

                        if (newMinutes > this.timeLimitMinutes) {
                            const added = newMinutes - this.timeLimitMinutes;
                            this.timeLimitMinutes = newMinutes;
                            this.timeExtendedMessage = `🎉 Consultation time extended by +${added} min! New limit: ${newMinutes}m`;
                            this.showTimeExtendedBanner = true;
                            setTimeout(() => { this.showTimeExtendedBanner = false; }, 6000);
                        } else if (newMinutes && newMinutes !== this.timeLimitMinutes) {
                            this.timeLimitMinutes = newMinutes;
                        }

                        if (data.timer.client_credits !== undefined) {
                            this.userCredits = data.timer.client_credits;
                            window.dispatchEvent(new CustomEvent('credits-updated', { detail: { credits: data.timer.client_credits } }));
                        }
                    }
                })
                .catch(() => {});
            },

            markAsRead() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken) return;

                fetch(`/consultation/${this.consultationId}/messages/read`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .catch(() => {});
            },

            startTicker() {
                this.tickerInterval = setInterval(() => {
                    // Only deduct time when timer is running (doctor is active in room)
                    if (this.isTimerRunning && !this.isExpired) {
                        this.consumedSeconds = Math.min(this.totalDurationSeconds, this.consumedSeconds + 1);
                        this.remainingSeconds = Math.max(0, this.remainingSeconds - 1);

                        if (this.remainingSeconds <= 0) {
                            this.isExpired = true;
                            this.isTimerRunning = false;
                        }
                    }
                }, 1000);
            },

            fetchMessages() {
                fetch(`/consultation/${this.consultationId}/messages`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const previousCount = this.messages.length;
                        const tempMessages = this.messages.filter(m => typeof m.id === 'string' && m.id.startsWith('temp-'));
                        this.messages = [...data.messages, ...tempMessages];
                        if (this.messages.length > previousCount) {
                            this.scrollToBottom();
                        }

                        if (data.timer) {
                            this.remainingSeconds = data.timer.remaining_seconds;
                            this.consumedSeconds = data.timer.consumed_seconds;
                            this.totalDurationSeconds = data.timer.total_seconds;
                            this.doctorPresent = data.timer.doctor_present;
                            this.clientPresent = data.timer.client_present;
                            this.isTimerRunning = data.timer.is_timer_running && data.timer.consultation_status !== 'completed';
                            this.pendingExtension = data.timer.pending_extension;
                            this.isExpired = data.timer.is_expired || data.timer.consultation_status === 'completed' || this.remainingSeconds <= 0;

                            // Dynamically synchronize timeLimitMinutes
                            const newMinutes = data.timer.duration_minutes !== undefined 
                                ? Number(data.timer.duration_minutes) 
                                : (data.timer.total_seconds ? Math.round(Number(data.timer.total_seconds) / 60) : this.timeLimitMinutes);

                            if (newMinutes > this.timeLimitMinutes) {
                                const added = newMinutes - this.timeLimitMinutes;
                                this.timeLimitMinutes = newMinutes;
                                this.timeExtendedMessage = `🎉 Consultation time extended by +${added} min! New limit: ${newMinutes}m`;
                                this.showTimeExtendedBanner = true;
                                setTimeout(() => { this.showTimeExtendedBanner = false; }, 6000);
                            } else if (newMinutes && newMinutes !== this.timeLimitMinutes) {
                                this.timeLimitMinutes = newMinutes;
                            }

                            if (data.timer.client_credits !== undefined) {
                                this.userCredits = data.timer.client_credits;
                                window.dispatchEvent(new CustomEvent('credits-updated', { detail: { credits: data.timer.client_credits } }));
                            }
                        }
                    }
                })
                .catch(err => console.warn('Chat fetch error:', err));
            },

            doctorAddTime(minutes) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken || this.isExtending) return;

                this.isExtending = true;
                fetch(`/consultation/${this.consultationId}/doctor-add-time`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ minutes: minutes })
                })
                .then(res => res.json())
                .then(data => {
                    this.isExtending = false;
                    if (data.status === 'success') {
                        this.timeLimitMinutes = data.duration_minutes;
                        this.remainingSeconds = data.remaining_seconds;
                        this.totalDurationSeconds = data.total_seconds;
                        this.isExpired = false;
                        this.showAddFreeTimeModal = false;
                    } else if (data.error) {
                        alert(data.error);
                    }
                })
                .catch(() => { this.isExtending = false; });
            },

            submitExtensionRequest() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken || this.isExtending) return;

                this.isExtending = true;
                fetch(`/consultation/${this.consultationId}/request-extension`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ minutes: this.selectedMinutes })
                })
                .then(res => res.json())
                .then(data => {
                    this.isExtending = false;
                    if (data.status === 'success') {
                        this.pendingExtension = data.extension;
                        this.showExtensionModal = false;
                    } else if (data.error) {
                        alert(data.error);
                    }
                })
                .catch(() => { this.isExtending = false; });
            },

            approveExtension(extensionId) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken || this.isExtending) return;

                this.isExtending = true;
                fetch(`/consultation/${this.consultationId}/extensions/${extensionId}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(res => res.json())
                .then(data => {
                    this.isExtending = false;
                    if (data.status === 'success') {
                        this.timeLimitMinutes = data.duration_minutes;
                        this.remainingSeconds = data.remaining_seconds;
                        this.totalDurationSeconds = data.total_seconds;
                        this.pendingExtension = null;
                        this.isExpired = false;
                        if (data.client_credits !== undefined) {
                            this.userCredits = data.client_credits;
                            window.dispatchEvent(new CustomEvent('credits-updated', { detail: { credits: data.client_credits } }));
                        }
                    } else if (data.error) {
                        alert(data.error);
                    }
                })
                .catch(() => { this.isExtending = false; });
            },

            declineExtension(extensionId) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken || this.isExtending) return;

                this.isExtending = true;
                fetch(`/consultation/${this.consultationId}/extensions/${extensionId}/decline`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(res => res.json())
                .then(data => {
                    this.isExtending = false;
                    if (data.status === 'success') {
                        this.pendingExtension = null;
                    } else if (data.error) {
                        alert(data.error);
                    }
                })
                .catch(() => { this.isExtending = false; });
            },

            cancelExtension(extensionId) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken || this.isExtending) return;

                this.isExtending = true;
                fetch(`/consultation/${this.consultationId}/extensions/${extensionId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(res => res.json())
                .then(data => {
                    this.isExtending = false;
                    if (data.status === 'success') {
                        this.pendingExtension = null;
                    }
                })
                .catch(() => { this.isExtending = false; });
            },

            handleFileChange(e) {
                if (e.target.files.length > 0) {
                    const file = e.target.files[0];
                    if (file.size > 50 * 1024 * 1024) {
                        alert('File is too large. Maximum file upload size is 50MB.');
                        this.clearFile();
                        return;
                    }
                    this.attachmentFile = file;
                    this.attachmentName = file.name;
                    this.attachmentFileSize = file.size;
                }
            },

            clearFile() {
                this.attachmentFile = null;
                this.attachmentName = '';
                this.attachmentFileSize = 0;
                const fileInput = document.getElementById('chat-file');
                if (fileInput) fileInput.value = '';
            },

            openMediaPreview(url, type = 'image') {
                if (!url) return;
                this.previewMediaUrl = url;
                this.previewMediaType = type;
                if (type === 'image') this.previewImageUrl = url;
            },

            openImagePreview(url) {
                this.openMediaPreview(url, 'image');
            },

            openVideoPreview(url) {
                this.openMediaPreview(url, 'video');
            },

            closeMediaPreview() {
                this.previewMediaUrl = null;
                this.previewMediaType = null;
                this.previewImageUrl = null;
                const v = document.getElementById('modal-video-player');
                if (v) {
                    v.pause();
                    v.currentTime = 0;
                }
            },

            sendMessage() {
                if (this.isExpired) return;
                if (!this.newMessage.trim() && !this.attachmentFile) return;

                const text = this.newMessage;
                const file = this.attachmentFile;
                this.newMessage = '';
                this.clearFile();

                let attachmentType = null;
                if (file) {
                    const ext = (file.name.split('.').pop() || '').toLowerCase();
                    const videoExts = ['mp4', 'mov', 'webm', 'avi', 'mkv', 'ogv', 'm4v', '3gp'];
                    const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                    if ((file.type && file.type.startsWith('image/')) || imageExts.includes(ext)) {
                        attachmentType = 'image';
                    } else if ((file.type && file.type.startsWith('video/')) || videoExts.includes(ext)) {
                        attachmentType = 'video';
                    } else {
                        attachmentType = 'document';
                    }
                }

                // Optimistically render message immediately (0ms delay for sender)
                const tempId = 'temp-' + Date.now();
                const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                this.messages.push({
                    id: tempId,
                    sender_name: 'You',
                    is_me: true,
                    message: text,
                    attachment_url: file ? URL.createObjectURL(file) : null,
                    attachment_type: attachmentType,
                    created_at: nowTime,
                    is_read: false,
                    read_at: null,
                });
                this.scrollToBottom();

                this.isSending = true;
                const formData = new FormData();
                formData.append('message', text);
                if (file) {
                    formData.append('attachment', file);
                }

                const socketId = (window.Echo && typeof window.Echo.socketId === 'function') ? window.Echo.socketId() : '';
                const headers = {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                };
                if (socketId) {
                    headers['X-Socket-ID'] = socketId;
                }

                fetch(`/consultation/${this.consultationId}/messages`, {
                    method: 'POST',
                    headers: headers,
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    this.isSending = false;
                    if (data.status === 'success') {
                        const idx = this.messages.findIndex(m => m.id === tempId);
                        if (idx !== -1) {
                            this.messages.splice(idx, 1, data.data);
                        } else {
                            const exists = this.messages.some(m => m.id === data.data.id);
                            if (!exists) this.messages.push(data.data);
                        }
                    } else if (data.error) {
                        alert(data.error);
                        this.messages = this.messages.filter(m => m.id !== tempId);
                    }
                })
                .catch((err) => {
                    this.isSending = false;
                    console.error('Chat send error:', err);
                });
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    const el = document.getElementById('messages-container');
                    if (el) el.scrollTop = el.scrollHeight;
                });
            }
        }
    }
</script>
@endpush
@endsection
