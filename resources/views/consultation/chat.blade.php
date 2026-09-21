@extends('layouts.app')

@section('title', 'Live Consultation Chat #' . $consultation->consultation_number)

@section('content')
<div class="max-w-4xl mx-auto flex flex-col h-[calc(100vh-10rem)] min-h-[550px]" 
     x-data="chatComponent({{ $consultation->id }}, {{ $user->id }}, {{ $isVet ? 'true' : 'false' }}, {{ $timer['remaining_seconds'] }}, {{ $timer['consumed_seconds'] }}, {{ $timer['total_seconds'] }}, {{ $consultation->duration_minutes ?: 15 }}, {{ $creditsPerMinute }}, {{ $userCredits }}, {{ json_encode($extensionPackages) }})">

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
            <!-- Doctor Presence Status Badge -->
            <div class="flex items-center space-x-1.5 px-2.5 py-1.5 rounded-xl text-xs font-bold shrink-0 transition-all"
                 :class="doctorPresent ? (isTimerRunning ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-brand-500/20 text-brand-300 border border-brand-500/30') : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'">
                <span class="w-2 h-2 rounded-full shrink-0" :class="doctorPresent ? 'bg-emerald-400 animate-ping' : 'bg-amber-400 animate-pulse'"></span>
                <span class="hidden xs:inline" x-text="doctorPresent ? (isTimerRunning ? (isVet && !clientPresent ? 'Deducting (Client away)' : 'Doctor Active') : 'Doctor Present') : 'Waiting for Doctor (Paused)'"></span>
                <span class="xs:hidden" x-text="doctorPresent ? 'Active' : 'Paused'"></span>
            </div>

            <!-- Countdown Timer Badge -->
            <div class="px-2.5 sm:px-3 py-1.5 rounded-xl border text-xs font-mono font-bold flex items-center space-x-1.5 transition-all shadow-sm shrink-0"
                 :class="remainingSeconds <= 15 ? (remainingSeconds > 0 ? 'bg-rose-500/20 border-rose-500 text-rose-300 ring-2 ring-rose-500/50 animate-pulse' : 'bg-rose-900/60 border-rose-800 text-rose-300') : (isTimerRunning ? 'bg-slate-900 border-slate-700 text-brand-400' : 'bg-slate-900 border-amber-700/60 text-amber-400')">
                <i class="fa-solid fa-hourglass-half text-xs shrink-0" :class="remainingSeconds <= 15 && remainingSeconds > 0 ? 'text-rose-400 animate-spin' : (isTimerRunning ? 'text-brand-500' : 'text-amber-500')"></i>
                <div class="text-left leading-tight">
                    <div class="flex items-baseline space-x-1">
                        <span x-text="formattedRemaining" class="text-xs sm:text-sm font-black">00:00</span>
                        <span class="text-[9px] text-slate-400 font-sans hidden sm:inline" x-text="'/ ' + timeLimitMinutes + 'm'"></span>
                    </div>
                    <span class="text-[8px] uppercase font-sans block font-semibold"
                          :class="isExpired ? 'text-rose-400' : (remainingSeconds <= 15 ? 'text-rose-400' : (isTimerRunning ? 'text-emerald-400' : 'text-amber-400'))"
                          x-text="isExpired ? 'Expired' : (remainingSeconds <= 15 ? 'Ending!' : (isTimerRunning ? 'Deducting' : 'Timer Paused'))"></span>
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
                <a href="{{ route('vet.records.create', $consultation) }}" class="bg-teal-600 hover:bg-teal-500 text-white font-bold text-xs px-3 py-2 rounded-xl transition-all shrink-0">
                    Record
                </a>
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
            <span>Consultation time limit reached. All {{ $consultation->duration_minutes ?: 15 }} minutes have been consumed. Chat is in read-only mode.</span>
        </div>
        <div class="flex items-center space-x-2 self-end sm:self-auto">
            @if(auth()->user()->isVet())
                <button type="button" @click="showAddFreeTimeModal = true" class="bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-colors mr-1">
                    + Add Free Time to Reopen
                </button>
                <a href="{{ route('vet.records.create', $consultation) }}" class="bg-teal-600 hover:bg-teal-500 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-colors">Complete Medical Record</a>
            @else
                <button type="button" @click="showExtensionModal = true" class="bg-amber-600 hover:bg-amber-500 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-colors mr-1">
                    + Request Extension
                </button>
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

    <!-- Messages Container -->
    <div class="flex-grow bg-white border-x border-slate-200 p-6 overflow-y-auto space-y-4 custom-scrollbar" id="messages-container">
        <template x-for="msg in messages" :key="msg.id">
            <div class="flex flex-col" :class="msg.is_me ? 'items-end' : 'items-start'">
                <div class="flex items-center space-x-1.5 text-[11px] text-slate-400 mb-1">
                    <span class="font-semibold text-slate-600" x-text="msg.sender_name"></span>
                    <span>•</span>
                    <span x-text="msg.created_at"></span>
                </div>

                <div class="max-w-[75%] rounded-2xl p-4 text-xs shadow-sm"
                     :class="msg.is_me ? 'bg-brand-600 text-white rounded-tr-none' : 'bg-slate-100 text-slate-800 rounded-tl-none border border-slate-200/60'">
                    <p x-text="msg.message" class="whitespace-pre-wrap leading-relaxed" x-show="msg.message"></p>

                    <template x-if="msg.attachment_url">
                        <div class="mt-2 pt-2" :class="msg.message ? 'border-t border-white/20' : ''">
                            <template x-if="msg.attachment_type === 'image'">
                                <img :src="msg.attachment_url" class="rounded-xl max-h-48 object-cover shadow-sm cursor-pointer" @click="window.open(msg.attachment_url, '_blank')">
                            </template>
                            <template x-if="msg.attachment_type !== 'image'">
                                <a :href="msg.attachment_url" target="_blank" class="inline-flex items-center space-x-2 underline font-semibold">
                                    <i class="fa-solid fa-file-arrow-down text-sm"></i>
                                    <span>Download File Attachment</span>
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
        <div x-show="isExpired" class="text-center py-2 text-xs text-slate-500 font-medium">
            <i class="fa-solid fa-lock text-slate-400 mr-1.5"></i>
            Consultation time expired. Chat messaging is now closed.
        </div>
        <form @submit.prevent="sendMessage()" class="flex items-center space-x-3" x-show="!isExpired">
            <!-- File Upload Button -->
            <label class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center cursor-pointer hover:bg-slate-100 transition-colors shrink-0 shadow-sm"
                   :class="isExpired ? 'opacity-50 pointer-events-none' : ''">
                <i class="fa-solid fa-paperclip text-sm"></i>
                <input type="file" id="chat-file" class="hidden" @change="handleFileChange($event)" :disabled="isExpired">
            </label>

            <!-- Attachment File Preview Pill -->
            <div x-show="attachmentName" class="text-xs bg-brand-100 text-brand-800 px-3 py-1.5 rounded-xl border border-brand-200 flex items-center space-x-2">
                <span class="font-medium truncate max-w-[120px]" x-text="attachmentName"></span>
                <button type="button" @click="clearFile()" class="text-brand-600 hover:text-rose-600"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <input type="text" x-model="newMessage" placeholder="Type your message to the {{ auth()->user()->isVet() ? 'client' : 'veterinarian' }}..."
                   class="flex-grow rounded-xl border-slate-200 text-xs py-3 px-4 focus:ring-brand-500 focus:border-brand-500 shadow-sm"
                   :disabled="isSending || isExpired">

            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs px-5 py-3 rounded-xl shadow-md shadow-brand-600/30 transition-all flex items-center space-x-1.5 shrink-0"
                    :disabled="isSending || isExpired">
                <span>Send</span>
                <i class="fa-solid fa-paper-plane text-xs"></i>
            </button>
        </form>
    </div>

</div>

@push('scripts')
<script>
    function chatComponent(consultationId, userId, isVet, initialRemaining, initialConsumed, totalDuration, timeLimitMinutes, creditsPerMinute, userCredits, extensionPackages) {
        return {
            consultationId: consultationId,
            userId: userId,
            isVet: isVet,
            messages: [],
            newMessage: '',
            attachmentFile: null,
            attachmentName: '',
            isSending: false,
            pollTimer: null,
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
            isTimerRunning: isVet ? true : false,
            isExpired: (initialRemaining !== undefined && initialRemaining <= 0),

            // Extension Modals & State
            showAddFreeTimeModal: false,
            showExtensionModal: false,
            selectedMinutes: (extensionPackages && extensionPackages.length > 0) ? extensionPackages[0].minutes : 10,
            pendingExtension: null,
            isExtending: false,

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
                this.fetchMessages();
                this.startTicker();

                // Auto poll every 3 seconds for messages & presence/timer sync
                this.pollTimer = setInterval(() => {
                    this.fetchMessages();
                }, 3000);
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
                        this.messages = data.messages;
                        if (data.messages.length > previousCount) {
                            this.scrollToBottom();
                        }

                        if (data.timer) {
                            this.remainingSeconds = data.timer.remaining_seconds;
                            this.consumedSeconds = data.timer.consumed_seconds;
                            this.totalDurationSeconds = data.timer.total_seconds;
                            this.doctorPresent = data.timer.doctor_present;
                            this.clientPresent = data.timer.client_present;
                            this.isTimerRunning = data.timer.is_timer_running;
                            this.pendingExtension = data.timer.pending_extension;
                            this.isExpired = data.timer.is_expired || this.remainingSeconds <= 0;

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
                    this.attachmentFile = e.target.files[0];
                    this.attachmentName = this.attachmentFile.name;
                }
            },

            clearFile() {
                this.attachmentFile = null;
                this.attachmentName = '';
                const fileInput = document.getElementById('chat-file');
                if (fileInput) fileInput.value = '';
            },

            sendMessage() {
                if (this.isExpired) return;
                if (!this.newMessage.trim() && !this.attachmentFile) return;

                this.isSending = true;
                const formData = new FormData();
                formData.append('message', this.newMessage);
                if (this.attachmentFile) {
                    formData.append('attachment', this.attachmentFile);
                }

                fetch(`/consultation/${this.consultationId}/messages`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        this.messages.push(data.data);
                        this.newMessage = '';
                        this.clearFile();
                        this.scrollToBottom();
                    } else if (data.error) {
                        alert(data.error);
                    }
                    this.isSending = false;
                })
                .catch(() => { this.isSending = false; });
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
