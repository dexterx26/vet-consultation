@extends('layouts.app')

@section('title', 'Video Consultation — #' . $consultation->consultation_number)

@push('styles')
<script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
@endpush

@section('content')
<div class="max-w-5xl mx-auto space-y-4" x-data="videoRoomComponent('{{ $call->room_name }}', {{ $user->id }}, '{{ $user->name }}', {{ $isVet ? 'true' : 'false' }}, {{ $timeLimitSeconds }}, {{ $timeLimitMinutes }})">

    <!-- Top Video Bar -->
    <div class="bg-navy-800 text-white rounded-3xl p-4 sm:p-5 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4 border border-slate-700">
        <div class="flex items-center space-x-3 w-full sm:w-auto">
            <div class="w-10 h-10 rounded-2xl bg-brand-600 text-white flex items-center justify-center font-bold text-lg animate-pulse shrink-0">
                <i class="fa-solid fa-video"></i>
            </div>
            <div>
                <h1 class="font-bold text-base leading-tight">Live Video Consultation</h1>
                <p class="text-xs text-slate-300">
                    Room: <span class="font-mono text-brand-300">{{ $call->room_name }}</span> • 
                    Client: <strong class="text-white">{{ $consultation->client->name }}</strong> • 
                    Vet: <strong class="text-white">Dr. {{ $consultation->vet->name }}</strong>
                </p>
            </div>
        </div>

        <div class="flex items-center space-x-3 w-full sm:w-auto justify-end">
            <!-- Connection Indicator -->
            <div class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold"
                 :class="remoteConnected ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'">
                <span class="w-2 h-2 rounded-full" :class="remoteConnected ? 'bg-emerald-400 animate-ping' : 'bg-amber-400 animate-pulse'"></span>
                <span x-text="remoteConnected ? 'Connected Live' : 'Waiting for Peer'"></span>
            </div>

            <!-- Countdown Timer vs Time Limit -->
            <div class="px-3.5 py-1.5 rounded-xl border text-xs font-mono font-bold flex items-center space-x-2 transition-all shadow-sm"
                 :class="remainingSeconds <= 15 ? 'bg-rose-500/20 border-rose-500 text-rose-300 ring-2 ring-rose-500/50 animate-pulse' : 'bg-slate-900 border-slate-700 text-brand-400'">
                <i class="fa-solid fa-hourglass-half text-xs" :class="remainingSeconds <= 15 ? 'text-rose-400' : 'text-brand-500'"></i>
                <div class="text-left leading-tight">
                    <span x-text="formattedRemaining" class="text-sm font-black">01:00</span>
                    <span class="text-[9px] uppercase font-sans text-slate-400 block font-semibold"
                          x-text="remainingSeconds <= 15 ? 'Closing soon!' : 'Limit: {{ $timeLimitMinutes }}m'"></span>
                </div>
            </div>

            <a href="{{ route('consultation.chat', $consultation) }}" class="bg-slate-700 hover:bg-slate-600 text-white font-semibold text-xs px-3.5 py-2.5 rounded-xl transition-all flex items-center space-x-1.5">
                <i class="fa-solid fa-comments"></i>
                <span>Chat</span>
            </a>
        </div>
    </div>

    <!-- 15-Second Warning Alert Banner -->
    <div x-show="remainingSeconds <= 15 && remainingSeconds > 0"
         x-transition
         class="bg-rose-600/90 backdrop-blur text-white px-4 py-3 rounded-2xl flex items-center justify-between text-xs font-bold shadow-lg animate-pulse"
         style="display: none;">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-triangle-exclamation text-base"></i>
            <span>Consultation time limit approaching! This video call session will automatically end in <span x-text="remainingSeconds" class="font-mono underline font-black text-sm"></span> seconds.</span>
        </div>
        <span class="text-[10px] bg-rose-800/80 px-2 py-0.5 rounded uppercase">Time Limit Enforced</span>
    </div>

    <!-- Auto-ended notification overlay -->
    <div x-show="callTimeExpired"
         x-transition
         class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full text-center space-y-4 shadow-2xl border border-slate-200">
            <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-2xl mx-auto">
                <i class="fa-solid fa-stopwatch"></i>
            </div>
            <h3 class="font-bold text-slate-800 text-lg">Consultation Time Limit Reached</h3>
            <p class="text-xs text-slate-500">
                The {{ $timeLimitMinutes }}-minute video consultation period has completed. You are now being redirected to the consultation summary.
            </p>
            <div class="pt-2">
                <span class="text-xs text-brand-600 font-bold animate-pulse">Redirecting...</span>
            </div>
        </div>
    </div>

    <!-- Main Video Grid Container -->
    <div class="relative bg-navy-900 rounded-3xl overflow-hidden shadow-2xl border border-slate-800 aspect-video flex items-center justify-center min-h-[480px]">
        
        <!-- Remote Large Stream (Main Remote Participant) -->
        <video id="remoteVideo" autoplay playsinline class="w-full h-full object-cover" x-show="remoteConnected"></video>

        <!-- Remote Waiting Placeholder Overlay -->
        <div x-show="!remoteConnected" class="absolute inset-0 bg-navy-900/95 backdrop-blur flex flex-col items-center justify-center text-center p-6 space-y-4 z-10">
            <div class="w-20 h-20 rounded-full bg-brand-950 border border-brand-500/30 flex items-center justify-center text-brand-400 text-3xl animate-bounce">
                <i class="fa-solid fa-user-doctor" x-show="!isVet"></i>
                <i class="fa-solid fa-user" x-show="isVet"></i>
            </div>
            <div>
                <h3 class="text-white font-bold text-xl" x-text="isVet ? 'Waiting for client ({{ $consultation->client->name }}) to join call...' : 'Waiting for Dr. {{ $consultation->vet->name }} to join call...'"></h3>
                <p class="text-slate-400 text-xs mt-1 max-w-md">Your own camera preview is in the bottom-right corner. When the other participant joins, their video stream will display here on the main screen.</p>
            </div>
        </div>

        <!-- Local Self Video Preview (PIP in Bottom Right Corner) -->
        <div class="absolute bottom-6 right-6 w-44 sm:w-56 aspect-video bg-slate-950 rounded-2xl overflow-hidden border-2 border-brand-500 shadow-xl z-20">
            <video id="localVideo" autoplay playsinline muted class="w-full h-full object-cover transform -scale-x-100"></video>
            <span class="absolute bottom-2 left-2 bg-black/70 text-white text-[10px] font-bold px-2 py-0.5 rounded backdrop-blur border border-white/10">You (Self Preview)</span>
        </div>

        <!-- Floating Video Control Toolbar -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 bg-slate-900/90 backdrop-blur border border-slate-700/80 px-6 py-3 rounded-full flex items-center space-x-5 shadow-2xl z-30">
            <!-- Mute Mic Toggle -->
            <button type="button" @click="toggleMic()" :class="isMuted ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-200 hover:bg-slate-700'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all shadow-md" title="Toggle Microphone">
                <i class="fa-solid" :class="isMuted ? 'fa-microphone-slash' : 'fa-microphone'"></i>
            </button>

            <!-- Mute Camera Toggle -->
            <button type="button" @click="toggleCam()" :class="isCameraOff ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-200 hover:bg-slate-700'" class="w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all shadow-md" title="Toggle Camera">
                <i class="fa-solid" :class="isCameraOff ? 'fa-video-slash' : 'fa-video'"></i>
            </button>

            <!-- End Call Red Button -->
            <form id="endCallForm" method="POST" action="{{ route('consultation.video.end', $consultation) }}">
                @csrf
                <button type="submit" onclick="return confirm('End this video consultation call?');" class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm px-6 py-3 rounded-full shadow-lg shadow-rose-600/40 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-phone-slash"></i>
                    <span>End Call</span>
                </button>
            </form>
        </div>

    </div>

</div>

@push('scripts')
<script>
    function videoRoomComponent(roomName, userId, userName, isVet, timeLimitSeconds, timeLimitMinutes) {
        return {
            roomName: roomName,
            userId: userId,
            userName: userName,
            isVet: isVet,
            timeLimitSeconds: timeLimitSeconds || 60,
            timeLimitMinutes: timeLimitMinutes || 1,
            remoteConnected: false,
            isMuted: false,
            isCameraOff: false,
            durationSeconds: 0,
            remainingSeconds: timeLimitSeconds || 60,
            callTimeExpired: false,
            timerInterval: null,
            localStream: null,
            peer: null,

            get formattedDuration() {
                const mins = Math.floor(this.durationSeconds / 60).toString().padStart(2, '0');
                const secs = (this.durationSeconds % 60).toString().padStart(2, '0');
                return `${mins}:${secs}`;
            },

            get formattedRemaining() {
                const mins = Math.floor(this.remainingSeconds / 60).toString().padStart(2, '0');
                const secs = (this.remainingSeconds % 60).toString().padStart(2, '0');
                return `${mins}:${secs}`;
            },

            init() {
                this.remainingSeconds = this.timeLimitSeconds;
                this.startLocalStream();
                this.startTimer();
            },

            startLocalStream() {
                navigator.mediaDevices.getUserMedia({ video: true, audio: true })
                .then(stream => {
                    this.localStream = stream;
                    const localVideo = document.getElementById('localVideo');
                    if (localVideo) localVideo.srcObject = stream;
                    
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

            startTimer() {
                this.timerInterval = setInterval(() => {
                    this.durationSeconds++;
                    this.remainingSeconds = Math.max(0, this.timeLimitSeconds - this.durationSeconds);

                    // Automatic call termination when time limit expires
                    if (this.remainingSeconds <= 0 && !this.callTimeExpired) {
                        this.callTimeExpired = true;
                        clearInterval(this.timerInterval);
                        
                        // Close media tracks
                        if (this.localStream) {
                            this.localStream.getTracks().forEach(t => t.stop());
                        }

                        // Auto-submit end call after 1.5 seconds so user sees notification
                        setTimeout(() => {
                            const endForm = document.getElementById('endCallForm');
                            if (endForm) {
                                endForm.submit();
                            }
                        }, 1500);
                    }
                }, 1000);
            }
        }
    }
</script>
@endpush
@endsection
