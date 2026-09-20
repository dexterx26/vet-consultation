@extends('layouts.app')

@section('title', 'Video Consultation — #' . $consultation->consultation_number)

@push('styles')
<script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
@endpush

@section('content')
<div class="max-w-5xl mx-auto space-y-3 sm:space-y-4 px-1 sm:px-0" x-data="videoRoomComponent('{{ $call->room_name }}', {{ $user->id }}, '{{ $user->name }}', {{ $isVet ? 'true' : 'false' }}, {{ $timeLimitSeconds }}, {{ $timeLimitMinutes }})">

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

        <div class="flex items-center space-x-2 sm:space-x-3 w-full sm:w-auto justify-between sm:justify-end shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-700/60">
            <!-- Connection Indicator -->
            <div class="flex items-center space-x-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-bold shrink-0 transition-all"
                 :class="remoteConnected ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'">
                <span class="w-2 h-2 rounded-full shrink-0" :class="remoteConnected ? 'bg-emerald-400 animate-ping' : 'bg-amber-400 animate-pulse'"></span>
                <span class="hidden xs:inline" x-text="remoteConnected ? 'Connected Live' : 'Waiting for Peer'"></span>
                <span class="xs:hidden" x-text="remoteConnected ? 'Live' : 'Waiting'"></span>
            </div>

            <!-- Countdown Timer vs Time Limit -->
            <div class="px-2.5 sm:px-3.5 py-1.5 rounded-xl border text-xs font-mono font-bold flex items-center space-x-1.5 sm:space-x-2 transition-all shadow-sm shrink-0"
                 :class="remainingSeconds <= 15 ? 'bg-rose-500/20 border-rose-500 text-rose-300 ring-2 ring-rose-500/50 animate-pulse' : 'bg-slate-900 border-slate-700 text-brand-400'">
                <i class="fa-solid fa-hourglass-half text-xs shrink-0" :class="remainingSeconds <= 15 ? 'text-rose-400' : 'text-brand-500'"></i>
                <div class="text-left leading-tight">
                    <span x-text="formattedRemaining" class="text-xs sm:text-sm font-black">01:00</span>
                    <span class="text-[8px] sm:text-[9px] uppercase font-sans text-slate-400 block font-semibold"
                          x-text="remainingSeconds <= 15 ? 'Ending!' : '{{ $timeLimitMinutes }}m limit'"></span>
                </div>
            </div>

            <a href="{{ route('consultation.chat', $consultation) }}" class="bg-slate-700 hover:bg-slate-600 text-white font-semibold text-xs px-2.5 sm:px-3.5 py-2 sm:py-2.5 rounded-xl transition-all flex items-center space-x-1.5 shrink-0" title="Switch to text chat">
                <i class="fa-solid fa-comments"></i>
                <span class="hidden xs:inline">Chat</span>
            </a>
        </div>
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
        <span class="text-[9px] sm:text-[10px] bg-rose-800/80 px-2 py-0.5 rounded uppercase self-end sm:self-auto shrink-0">Time Limit Enforced</span>
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
            <h3 class="font-bold text-slate-800 text-base sm:text-lg">Consultation Time Limit Reached</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                The {{ $timeLimitMinutes }}-minute video consultation period has completed. You are now being redirected to the consultation summary.
            </p>
            <div class="pt-2">
                <span class="text-xs text-brand-600 font-bold animate-pulse">Redirecting...</span>
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
            currentCall: null,
            facingMode: 'user',
            hasMultipleCameras: false,
            fitMode: 'cover',
            pipMinimized: false,
            isFullscreen: false,

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
                this.checkCameraDevices();
                this.startLocalStream();
                this.startTimer();

                document.addEventListener('fullscreenchange', () => {
                    this.isFullscreen = !!document.fullscreenElement;
                });
                document.addEventListener('webkitfullscreenchange', () => {
                    this.isFullscreen = !!document.webkitFullscreenElement;
                });
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
