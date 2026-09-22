@extends('layouts.app')

@section('title', 'Video Consultation — #' . $consultation->consultation_number)

@push('styles')
<script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
@endpush

@section('content')
<div class="max-w-5xl mx-auto space-y-3 sm:space-y-4 px-1 sm:px-0" 
     x-data="videoRoomComponent('{{ $call->room_name }}', {{ $user->id }}, '{{ $user->name }}', {{ $isVet ? 'true' : 'false' }}, {{ $remainingSeconds }}, {{ $consumedSeconds }}, {{ $totalDurationSeconds }}, {{ $timeLimitMinutes }}, {{ $consultation->id }}, {{ $creditsPerMinute }}, {{ $userCredits }}, {{ json_encode($extensionPackages) }})">

    <!-- Top Video Bar -->
    <div class="bg-navy-800 text-white rounded-2xl sm:rounded-3xl p-3 sm:p-5 shadow-xl flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4 border border-slate-700">
        <div class="flex items-center space-x-3 w-full sm:w-auto min-w-0">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-brand-600 text-white flex items-center justify-center font-bold text-base sm:text-lg animate-pulse shrink-0 shadow-md">
                <i class="fa-solid fa-video"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center space-x-2">
                    <h1 class="font-bold text-sm sm:text-base leading-tight truncate">Live Video Consultation</h1>
                    <span class="text-[10px] font-mono text-brand-300 bg-brand-950/80 px-2 py-0.5 rounded border border-brand-800 hidden xs:inline">#{{ $consultation->consultation_number }}</span>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-300 leading-snug mt-0.5 truncate sm:whitespace-normal">
                    Pets: <strong class="text-amber-300">{{ $consultation->all_pets->pluck('name')->join(', ') }}</strong> • 
                    {{ $isVet ? 'Client:' : 'Vet:' }} 
                    <strong class="text-white">{{ $isVet ? $consultation->client->name : 'Dr. ' . $consultation->vet->name }}</strong>
                </p>
            </div>
        </div>

        <div class="flex items-center space-x-2 sm:space-x-2.5 w-full sm:w-auto justify-between sm:justify-end shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-700/60 flex-wrap sm:flex-nowrap gap-y-2">
            <!-- Reverb Live WebSocket Indicator -->
            <div class="hidden sm:flex items-center space-x-1.5 px-2.5 py-1.5 rounded-xl text-[11px] font-bold border transition-colors shadow-sm shrink-0"
                 :class="isEchoConnected ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-slate-700/40 text-slate-400 border-slate-600'"
                 :title="isEchoConnected ? 'Connected via Laravel Reverb WebSockets' : 'Connecting to Reverb WebSockets...'">
                <i class="fa-solid fa-bolt text-[9px]" :class="isEchoConnected ? 'text-amber-300 animate-pulse' : 'text-slate-400'"></i>
                <span x-text="isEchoConnected ? 'Reverb' : 'Connecting'"></span>
            </div>

            <!-- Doctor Presence / Status Indicator -->
            <div class="flex items-center space-x-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-bold shrink-0 transition-all"
                 :class="callTimeExpired ? 'bg-slate-800 text-slate-400 border border-slate-700' : (doctorPresent ? (isTimerRunning ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-brand-500/20 text-brand-300 border border-brand-500/30') : 'bg-amber-500/20 text-amber-400 border border-amber-500/30')">
                <span class="w-2 h-2 rounded-full shrink-0" :class="callTimeExpired ? 'bg-slate-400' : (doctorPresent ? 'bg-emerald-400 animate-ping' : 'bg-amber-400 animate-pulse')"></span>
                <span class="hidden xs:inline" x-text="callTimeExpired ? 'Consultation Ended' : (doctorPresent ? (remoteConnected ? 'Connected Live' : (isVet ? 'Deducting (Client not in call)' : 'Doctor Present')) : 'Waiting for Doctor (Paused)')"></span>
                <span class="xs:hidden" x-text="callTimeExpired ? 'Ended' : (doctorPresent ? 'Live' : 'Paused')"></span>
            </div>

            <!-- Countdown Timer vs Time Limit -->
            <div class="px-2.5 sm:px-3.5 py-1.5 rounded-xl border text-xs font-mono font-bold flex items-center space-x-1.5 sm:space-x-2 transition-all shadow-sm shrink-0"
                 :class="callTimeExpired ? 'bg-slate-900 border-slate-700 text-slate-400' : (remainingSeconds <= 15 ? 'bg-rose-500/20 border-rose-500 text-rose-300 ring-2 ring-rose-500/50 animate-pulse' : (isTimerRunning ? 'bg-slate-900 border-slate-700 text-brand-400' : 'bg-slate-900 border-amber-700/60 text-amber-400'))">
                <i class="fa-solid fa-hourglass-half text-xs shrink-0" :class="callTimeExpired ? 'text-slate-500' : (remainingSeconds <= 15 ? 'text-rose-400 animate-spin' : (isTimerRunning ? 'text-brand-500' : 'text-amber-500'))"></i>
                <div class="text-left leading-tight">
                    <div class="flex items-baseline space-x-1">
                        <span x-text="formattedRemaining" class="text-xs sm:text-sm font-black">00:00</span>
                        <span class="text-[9px] text-slate-400 font-sans hidden sm:inline" x-text="'/ ' + timeLimitMinutes + 'm'"></span>
                    </div>
                    <span class="text-[8px] sm:text-[9px] uppercase font-sans block font-semibold"
                          :class="callTimeExpired ? 'text-slate-400' : (remainingSeconds <= 15 ? 'text-rose-400' : (isTimerRunning ? 'text-emerald-400' : 'text-amber-400'))"
                          x-text="callTimeExpired ? (remainingSeconds <= 0 ? 'Expired' : 'Ended') : (remainingSeconds <= 15 ? 'Ending!' : (isTimerRunning ? 'Deducting' : 'Timer Paused'))"></span>
                </div>
            </div>

            <!-- Doctor Add Free Time Button (Free of charge) -->
            @if($isVet)
                <button type="button" @click="showAddFreeTimeModal = true"
                        class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-2.5 sm:px-3 py-2 rounded-xl transition-all flex items-center space-x-1.5 shadow-sm shrink-0"
                        title="Add complimentary time (free of charge to customer)">
                    <i class="fa-solid fa-gift text-xs"></i>
                    <span class="hidden xs:inline">+ Free Time</span>
                    <span class="xs:hidden">+Time</span>
                </button>
            @else
                <!-- Client Request Extension Button (Credits) -->
                <button type="button" @click="showExtensionModal = true"
                        class="bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs px-2.5 sm:px-3 py-2 rounded-xl transition-all flex items-center space-x-1.5 shadow-sm shrink-0"
                        title="Request consultation extension with credits">
                    <i class="fa-solid fa-hourglass-start text-xs"></i>
                    <span class="hidden xs:inline">Add Time</span>
                    <span class="xs:hidden">+Time</span>
                </button>
            @endif

            <a href="{{ route('consultation.chat', $consultation) }}" class="bg-slate-700 hover:bg-slate-600 text-white font-semibold text-xs px-2.5 sm:px-3.5 py-2 sm:py-2.5 rounded-xl transition-all flex items-center space-x-1.5 shrink-0" title="Switch to text chat">
                <i class="fa-solid fa-comments"></i>
                <span class="hidden xs:inline">Chat</span>
            </a>
        </div>
    </div>

    <!-- Vet Pending Extension Approval Banner -->
    <div x-show="pendingExtension && isVet"
         x-transition
         class="bg-amber-500 text-slate-950 px-4 py-3 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs font-bold shadow-xl border-2 border-amber-300 animate-pulse"
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
         class="bg-slate-900 border border-brand-500 text-white px-4 py-3 rounded-2xl flex items-center justify-between gap-3 text-xs font-medium shadow-xl"
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
         class="bg-emerald-600 text-white px-4 py-3 rounded-2xl flex items-center justify-between gap-3 text-xs font-bold shadow-xl border border-emerald-400"
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
         class="bg-rose-600/95 backdrop-blur text-white px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-xl sm:rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs font-bold shadow-lg animate-pulse"
         style="display: none;">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-triangle-exclamation text-base shrink-0"></i>
            <span>Consultation limit approaching! Call ends in <span x-text="remainingSeconds" class="font-mono underline font-black text-sm"></span> seconds.</span>
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

    <!-- Auto-ended notification overlay -->
    <div x-show="callTimeExpired"
         x-transition
         class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full text-center space-y-4 shadow-2xl border border-slate-200">
            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-xl sm:text-2xl mx-auto shadow-inner">
                <i class="fa-solid fa-stopwatch"></i>
            </div>
            <h3 class="font-bold text-slate-800 text-base sm:text-lg" x-text="remainingSeconds <= 0 ? 'Consultation Time Limit Reached' : 'Consultation Ended'">Consultation Ended</h3>
            <p class="text-xs text-slate-500 leading-relaxed" x-text="remainingSeconds <= 0 ? ('The ' + timeLimitMinutes + '-minute video consultation period has completed. You are now being redirected.') : 'The consultation has ended. You are now being redirected to the summary.'">
                The consultation has ended. You are now being redirected.
            </p>
            @if($isVet)
                <template x-if="remainingSeconds <= 0">
                    <div class="pt-1">
                        <button type="button" @click="callTimeExpired = false; showAddFreeTimeModal = true;"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2 rounded-xl shadow">
                            + Add Complimentary Time to Continue
                        </button>
                    </div>
                </template>
            @endif
            <div class="pt-2">
                <span class="text-xs text-brand-600 font-bold animate-pulse">Redirecting...</span>
            </div>
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
                Add extra consultation minutes. This time is completely free for your client and immediately extends the call countdown.
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

    <!-- Main Video Grid Container -->
    <div id="videoRoomContainer"
         class="relative bg-navy-900 overflow-hidden shadow-2xl border border-slate-800 w-full flex items-center justify-center select-none transition-all duration-300"
         :class="isFullscreen 
            ? 'fixed inset-0 z-50 rounded-none w-screen h-screen max-h-none border-0' 
            : 'rounded-2xl sm:rounded-3xl h-[62vh] min-h-[380px] max-h-[660px] sm:h-auto sm:min-h-[480px] sm:max-h-none sm:aspect-video'">
        
        <!-- Remote Large Stream (Main Remote Participant) -->
        <video id="remoteVideo" autoplay playsinline
               class="w-full h-full transition-all duration-300"
               :class="fitMode === 'cover' ? 'object-cover' : 'object-contain'"
               x-show="remoteConnected"></video>

        <!-- Remote Waiting Placeholder Overlay -->
        <div x-show="!remoteConnected" class="absolute inset-0 bg-navy-900/95 backdrop-blur flex flex-col items-center justify-center text-center p-4 sm:p-6 space-y-3 sm:space-y-4 z-10">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-brand-950 border border-brand-500/30 flex items-center justify-center text-brand-400 text-2xl sm:text-3xl animate-bounce shadow-lg">
                <i class="fa-solid fa-user-doctor" x-show="!isVet"></i>
                <i class="fa-solid fa-user" x-show="isVet"></i>
            </div>
            <div class="max-w-sm sm:max-w-md px-2">
                <h3 class="text-white font-bold text-base sm:text-xl leading-tight" x-text="isVet ? 'Waiting for client ({{ $consultation->client->name }}) to join call...' : 'Waiting for Dr. {{ $consultation->vet->name }} to join call...'"></h3>
                <p class="text-slate-400 text-xs mt-1.5 leading-relaxed">Your camera preview is active in the corner. When the other participant joins, their video stream will display here on the main screen.</p>
            </div>
        </div>

        <!-- Top Left Quick Actions (Fullscreen & Stream Fit Mode) -->
        <div class="absolute top-3 left-3 flex items-center space-x-2 z-20">
            <!-- Fullscreen Toggle -->
            <button type="button" @click="toggleFullscreen()"
                    class="bg-slate-900/80 hover:bg-slate-800 text-slate-200 hover:text-white px-2.5 py-1.5 rounded-xl backdrop-blur border border-slate-700/80 text-xs flex items-center space-x-1.5 transition-all shadow-md focus:outline-none"
                    title="Toggle Fullscreen">
                <i class="fa-solid" :class="isFullscreen ? 'fa-compress' : 'fa-expand'"></i>
                <span class="hidden sm:inline text-[11px] font-medium" x-text="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen'"></span>
            </button>

            <!-- Video Fit Toggle (Cover vs Contain) -->
            <button type="button" @click="fitMode = (fitMode === 'cover' ? 'contain' : 'cover')"
                    x-show="remoteConnected"
                    class="bg-slate-900/80 hover:bg-slate-800 text-slate-200 hover:text-white px-2.5 py-1.5 rounded-xl backdrop-blur border border-slate-700/80 text-xs flex items-center space-x-1.5 transition-all shadow-md focus:outline-none"
                    :title="fitMode === 'cover' ? 'Fit entire video without cropping' : 'Fill entire video display'">
                <i class="fa-solid" :class="fitMode === 'cover' ? 'fa-compress' : 'fa-arrows-alt'"></i>
                <span class="hidden sm:inline text-[11px] font-medium" x-text="fitMode === 'cover' ? 'Fit Window' : 'Fill Screen'"></span>
            </button>
        </div>

        <!-- Local Self Video Preview (PIP: Top-Right on Mobile, Bottom-Right on Desktop) -->
        <div class="absolute top-3 right-3 sm:top-auto sm:bottom-6 sm:right-6 transition-all duration-300 z-20"
             :class="pipMinimized ? 'w-10 h-10 sm:w-12 sm:h-12' : 'w-24 xs:w-28 sm:w-56 aspect-[3/4] sm:aspect-video'">
            
            <div class="relative w-full h-full bg-slate-950 rounded-xl sm:rounded-2xl overflow-hidden border sm:border-2 border-brand-500/90 shadow-2xl flex items-center justify-center">
                <!-- Local Video -->
                <video id="localVideo" autoplay playsinline muted
                       class="w-full h-full object-cover transition-transform duration-300"
                       :class="facingMode === 'user' ? 'transform -scale-x-100' : ''"
                       x-show="!pipMinimized && !isCameraOff"></video>

                <!-- Camera Off in PIP -->
                <div x-show="isCameraOff && !pipMinimized" class="w-full h-full flex flex-col items-center justify-center bg-slate-900 text-slate-400 p-2 text-center">
                    <i class="fa-solid fa-video-slash text-xs sm:text-base mb-1 text-rose-400"></i>
                    <span class="text-[8px] sm:text-[10px] font-medium leading-none">Off</span>
                </div>

                <!-- Label & Mic muted status inside PIP -->
                <div class="absolute bottom-1 sm:bottom-2 left-1 sm:left-2 right-1 sm:right-2 flex items-center justify-between pointer-events-none" x-show="!pipMinimized">
                    <span class="bg-black/75 text-white text-[8px] sm:text-[10px] font-bold px-1.5 py-0.5 rounded backdrop-blur border border-white/10 truncate max-w-[60px] sm:max-w-none">You</span>
                    <span x-show="isMuted" class="bg-rose-600/90 text-white text-[8px] px-1 sm:px-1.5 py-0.5 rounded backdrop-blur">
                        <i class="fa-solid fa-microphone-slash"></i>
                    </span>
                </div>

                <!-- Minimize / Expand PIP button -->
                <button type="button" @click="pipMinimized = !pipMinimized"
                        class="absolute top-1 right-1 bg-black/70 hover:bg-black text-white rounded-md w-4 h-4 sm:w-5 sm:h-5 flex items-center justify-center text-[9px] backdrop-blur z-30 transition-colors focus:outline-none"
                        :title="pipMinimized ? 'Expand Self Preview' : 'Minimize Self Preview'">
                    <i class="fa-solid" :class="pipMinimized ? 'fa-expand' : 'fa-minus'"></i>
                </button>
            </div>
        </div>

        <!-- Floating Video Control Toolbar (Centered at bottom, touch-friendly, non-overlapping) -->
        <div class="absolute bottom-3 sm:bottom-6 left-1/2 -translate-x-1/2 bg-slate-950/90 sm:bg-slate-900/90 backdrop-blur-md border border-slate-700/80 px-3 sm:px-6 py-2 sm:py-3 rounded-full flex items-center space-x-2.5 sm:space-x-4 shadow-2xl z-30 max-w-[calc(100%-1.5rem)] justify-center">
            
            <!-- Mute Mic Toggle -->
            <button type="button" @click="toggleMic()"
                    :class="isMuted ? 'bg-rose-600 text-white ring-2 ring-rose-500/50' : 'bg-slate-800 text-slate-200 hover:bg-slate-700'"
                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center text-sm sm:text-lg transition-all shadow-md shrink-0 focus:outline-none"
                    :title="isMuted ? 'Unmute Microphone' : 'Mute Microphone'">
                <i class="fa-solid" :class="isMuted ? 'fa-microphone-slash' : 'fa-microphone'"></i>
            </button>

            <!-- Mute Camera Toggle -->
            <button type="button" @click="toggleCam()"
                    :class="isCameraOff ? 'bg-rose-600 text-white ring-2 ring-rose-500/50' : 'bg-slate-800 text-slate-200 hover:bg-slate-700'"
                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center text-sm sm:text-lg transition-all shadow-md shrink-0 focus:outline-none"
                    :title="isCameraOff ? 'Turn Camera On' : 'Turn Camera Off'">
                <i class="fa-solid" :class="isCameraOff ? 'fa-video-slash' : 'fa-video'"></i>
            </button>

            <!-- Flip Camera (Front / Back Camera switcher for Mobile) -->
            <button type="button" @click="switchCamera()"
                    x-show="hasMultipleCameras && !isCameraOff"
                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-slate-800 text-slate-200 hover:bg-slate-700 flex items-center justify-center text-sm sm:text-base transition-all shadow-md shrink-0 focus:outline-none"
                    title="Switch Camera (Front / Back)">
                <i class="fa-solid fa-camera-rotate"></i>
            </button>

            <!-- End Call Red Button -->
            <form id="endCallForm" method="POST" action="{{ route('consultation.video.end', $consultation) }}" class="m-0 p-0 flex items-center">
                @csrf
                <button type="submit" onclick="return confirm('End this video consultation call?');"
                        class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs sm:text-sm px-3.5 sm:px-6 py-2.5 sm:py-3 rounded-full shadow-lg shadow-rose-600/40 transition-all flex items-center space-x-1.5 sm:space-x-2 shrink-0 focus:outline-none">
                    <i class="fa-solid fa-phone-slash text-xs sm:text-sm"></i>
                    <span class="hidden xs:inline">End Call</span>
                    <span class="xs:hidden">End</span>
                </button>
            </form>
        </div>

    </div>

</div>

@push('scripts')
<script>
    function videoRoomComponent(roomName, userId, userName, isVet, initialRemaining, initialConsumed, totalDuration, timeLimitMinutes, consultationId, creditsPerMinute, userCredits, extensionPackages) {
        return {
            roomName: roomName,
            userId: userId,
            userName: userName,
            isVet: isVet,
            consultationId: consultationId,
            timeLimitMinutes: timeLimitMinutes || 15,
            totalDurationSeconds: totalDuration || 900,
            consumedSeconds: initialConsumed || 0,
            remainingSeconds: initialRemaining !== undefined ? initialRemaining : 900,
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
            remoteConnected: false,
            isMuted: false,
            isCameraOff: false,
            callTimeExpired: '{{ $consultation->status }}' === 'completed' || initialRemaining <= 0,
            timerInterval: null,
            syncTimer: null,
            localStream: null,
            peer: null,
            currentCall: null,
            facingMode: 'user',
            hasMultipleCameras: false,
            fitMode: 'cover',
            pipMinimized: false,
            isFullscreen: false,
            isEchoConnected: false,

            // Extension Modals & State
            showAddFreeTimeModal: false,
            showExtensionModal: false,
            showTimeExtendedBanner: false,
            timeExtendedMessage: '',
            selectedMinutes: (extensionPackages && extensionPackages.length > 0) ? extensionPackages[0].minutes : 10,
            pendingExtension: null,
            isExtending: false,

            get selectedCredits() {
                const pkg = this.extensionPackages.find(p => p.minutes === this.selectedMinutes);
                return pkg ? pkg.credits : (this.selectedMinutes * (this.creditsPerMinute || 5));
            },

            get formattedDuration() {
                return this.formattedConsumed;
            },

            get formattedConsumed() {
                const mins = Math.floor(this.consumedSeconds / 60).toString().padStart(2, '0');
                const secs = (this.consumedSeconds % 60).toString().padStart(2, '0');
                return `${mins}:${secs}`;
            },

            get formattedRemaining() {
                const mins = Math.floor(this.remainingSeconds / 60).toString().padStart(2, '0');
                const secs = (this.remainingSeconds % 60).toString().padStart(2, '0');
                return `${mins}:${secs}`;
            },

            init() {
                this.checkCameraDevices();
                this.startLocalStream();
                this.startLocalTicker();
                this.initEcho();

                window.addEventListener('beforeunload', () => {
                    if (window.Echo) {
                        window.Echo.leave(`consultation.${this.consultationId}`);
                    }
                });

                // Periodic time sync & presence heartbeat every 3 seconds
                this.syncTime();
                this.syncTimer = setInterval(() => {
                    this.syncTime();
                }, 3000);

                document.addEventListener('fullscreenchange', () => {
                    this.isFullscreen = !!document.fullscreenElement;
                });
                document.addEventListener('webkitfullscreenchange', () => {
                    this.isFullscreen = !!document.webkitFullscreenElement;
                });
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
                            console.warn('Echo not found after 10 attempts in video room.');
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

                    channel.listen('.time.updated', (e) => {
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
                                this.callTimeExpired = false;
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
                                this.callTimeExpired = false;
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
                    console.warn('Error subscribing to Echo private channel in video room:', err);
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
                        this.isTimerRunning = data.timer.is_timer_running;
                        this.pendingExtension = data.timer.pending_extension;

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

                        this.isTimerRunning = data.timer.is_timer_running && data.timer.consultation_status !== 'completed';

                        if (this.remainingSeconds > 0 && this.callTimeExpired && data.timer.consultation_status !== 'completed') {
                            this.callTimeExpired = false;
                        }

                        if (data.timer.is_expired || data.timer.consultation_status === 'completed' || this.remainingSeconds <= 0) {
                            this.handleTimeExpired();
                        }
                    }
                })
                .catch(err => console.warn('Time sync error:', err));
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
                        this.callTimeExpired = false;
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
                        this.callTimeExpired = false;
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

            startLocalTicker() {
                this.timerInterval = setInterval(() => {
                    // Only tick down locally when timer is actively running (doctor is present)
                    if (this.isTimerRunning && !this.callTimeExpired) {
                        this.consumedSeconds = Math.min(this.totalDurationSeconds, this.consumedSeconds + 1);
                        this.remainingSeconds = Math.max(0, this.remainingSeconds - 1);

                        if (this.remainingSeconds <= 0) {
                            this.handleTimeExpired();
                        }
                    }
                }, 1000);
            },

            handleTimeExpired() {
                if (this.callTimeExpired) return;
                this.callTimeExpired = true;
                this.isTimerRunning = false;

                if (this.timerInterval) clearInterval(this.timerInterval);
                if (this.syncTimer) clearInterval(this.syncTimer);

                // Close media tracks
                if (this.localStream) {
                    this.localStream.getTracks().forEach(t => t.stop());
                }

                // Redirect after 1.5 seconds so user sees notification
                setTimeout(() => {
                    if (this.isVet) {
                        window.location.href = "{{ route('vet.records.create', $consultation) }}";
                    } else {
                        window.location.href = "{{ route('client.bookings.show', $consultation) }}";
                    }
                }, 1500);
            },

            checkCameraDevices() {
                if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
                    navigator.mediaDevices.enumerateDevices().then(devices => {
                        const videoInputs = devices.filter(d => d.kind === 'videoinput');
                        // Most mobile phones have 2 or more video inputs (front & back)
                        this.hasMultipleCameras = videoInputs.length > 1;
                    }).catch(() => {
                        // In case enumerateDevices is blocked before permission, check user agent
                        this.hasMultipleCameras = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                    });
                }
            },

            startLocalStream() {
                const constraints = {
                    video: { facingMode: this.facingMode },
                    audio: true
                };

                navigator.mediaDevices.getUserMedia(constraints)
                .then(stream => {
                    this.localStream = stream;
                    const localVideo = document.getElementById('localVideo');
                    if (localVideo) localVideo.srcObject = stream;
                    
                    // Re-check cameras once permission is granted
                    this.checkCameraDevices();

                    // Initialize WebRTC Peer connection
                    this.initPeer(stream);
                })
                .catch(err => {
                    console.log('Camera access notice:', err);
                });
            },

            initPeer(stream) {
                const myPeerId = this.isVet ? `${this.roomName}_vet` : `${this.roomName}_client`;
                const targetPeerId = this.isVet ? `${this.roomName}_client` : `${this.roomName}_vet`;

                try {
                    this.peer = new Peer(myPeerId);

                    this.peer.on('open', (id) => {
                        console.log('My PeerJS ID:', id);
                        this.callPeer(targetPeerId, stream);
                    });

                    this.peer.on('call', (call) => {
                        this.currentCall = call;
                        call.answer(stream);
                        call.on('stream', (remoteStream) => {
                            this.attachRemoteStream(remoteStream);
                        });
                    });

                    const retryCall = setInterval(() => {
                        if (this.remoteConnected || this.callTimeExpired) {
                            clearInterval(retryCall);
                        } else {
                            this.callPeer(targetPeerId, stream);
                        }
                    }, 3000);

                } catch(e) {
                    console.log('PeerJS initialization error:', e);
                }
            },

            callPeer(targetPeerId, stream) {
                if (!this.peer || this.callTimeExpired) return;
                const call = this.peer.call(targetPeerId, stream);
                if (call) {
                    this.currentCall = call;
                    call.on('stream', (remoteStream) => {
                        this.attachRemoteStream(remoteStream);
                    });
                }
            },

            attachRemoteStream(remoteStream) {
                this.remoteConnected = true;
                const remoteVideo = document.getElementById('remoteVideo');
                if (remoteVideo) {
                    remoteVideo.srcObject = remoteStream;
                }
            },

            toggleMic() {
                if (this.localStream) {
                    const audioTracks = this.localStream.getAudioTracks();
                    if (audioTracks.length > 0) {
                        audioTracks[0].enabled = !audioTracks[0].enabled;
                        this.isMuted = !audioTracks[0].enabled;
                    }
                }
            },

            toggleCam() {
                if (this.localStream) {
                    const videoTracks = this.localStream.getVideoTracks();
                    if (videoTracks.length > 0) {
                        videoTracks[0].enabled = !videoTracks[0].enabled;
                        this.isCameraOff = !videoTracks[0].enabled;
                    }
                }
            },

            async switchCamera() {
                if (!this.localStream || this.isCameraOff) return;
                this.facingMode = (this.facingMode === 'user') ? 'environment' : 'user';

                try {
                    // Stop current video tracks
                    this.localStream.getVideoTracks().forEach(track => track.stop());

                    const newStream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: this.facingMode },
                        audio: !this.isMuted
                    });

                    const newVideoTrack = newStream.getVideoTracks()[0];
                    const localVideo = document.getElementById('localVideo');
                    if (localVideo) {
                        localVideo.srcObject = newStream;
                    }

                    // Replace track on peer call
                    if (this.currentCall && this.currentCall.peerConnection) {
                        const senders = this.currentCall.peerConnection.getSenders();
                        const videoSender = senders.find(s => s.track && s.track.kind === 'video');
                        if (videoSender && newVideoTrack) {
                            videoSender.replaceTrack(newVideoTrack);
                        }
                    }

                    this.localStream = newStream;
                } catch (err) {
                    console.warn('Unable to switch camera:', err);
                }
            },

            toggleFullscreen() {
                const container = document.getElementById('videoRoomContainer');
                if (!container) return;

                if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                    if (container.requestFullscreen) {
                        container.requestFullscreen().catch(() => {
                            this.isFullscreen = !this.isFullscreen;
                        });
                    } else if (container.webkitRequestFullscreen) {
                        container.webkitRequestFullscreen();
                    } else {
                        // Fallback in-page fullscreen for browsers with strict/unsupported element fullscreen
                        this.isFullscreen = true;
                    }
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen().catch(() => {
                            this.isFullscreen = false;
                        });
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    } else {
                        this.isFullscreen = false;
                    }
                }
            }
        }
    }
</script>
@endpush
@endsection
