@extends('layouts.app')

@section('title', 'Live Consultation Chat #' . $consultation->consultation_number)

@section('content')
<div class="max-w-4xl mx-auto flex flex-col h-[calc(100vh-10rem)] min-h-[550px]" 
     x-data="chatComponent({{ $consultation->id }}, {{ $user->id }})">

    <!-- Chat Room Header -->
    <div class="bg-navy-800 text-white rounded-t-2xl p-4 shadow-md flex items-center justify-between shrink-0 border-b border-slate-700">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-brand-600 text-white flex items-center justify-center font-bold text-lg">
                <i class="fa-solid fa-comments"></i>
            </div>
            <div>
                <h1 class="font-bold text-base leading-tight text-white">Consultation #{{ $consultation->consultation_number }}</h1>
                <p class="text-xs text-slate-300">
                    Pet: <strong class="text-brand-300">{{ $consultation->pet->name }}</strong> • 
                    Client: <span class="text-slate-200">{{ $consultation->client->name }}</span> • 
                    Vet: <span class="text-slate-200">{{ $consultation->vet->name }}</span>
                </p>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            @if($consultation->type === 'video' || in_array($consultation->status, ['accepted', 'in_progress']))
                <a href="{{ route('consultation.video', $consultation) }}" class="bg-brand-500 hover:bg-brand-400 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition-all flex items-center space-x-1.5 shadow-md">
                    <i class="fa-solid fa-video"></i>
                    <span class="hidden sm:inline">Join Video Call</span>
                </a>
            @endif
            @if(auth()->user()->isVet())
                <a href="{{ route('vet.records.create', $consultation) }}" class="bg-teal-600 hover:bg-teal-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition-all">
                    Medical Record
                </a>
            @endif
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
        <form @submit.prevent="sendMessage()" class="flex items-center space-x-3">
            <!-- File Upload Button -->
            <label class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center cursor-pointer hover:bg-slate-100 transition-colors shrink-0 shadow-sm">
                <i class="fa-solid fa-paperclip text-sm"></i>
                <input type="file" id="chat-file" class="hidden" @change="handleFileChange($event)">
            </label>

            <!-- Attachment File Preview Pill -->
            <div x-show="attachmentName" class="text-xs bg-brand-100 text-brand-800 px-3 py-1.5 rounded-xl border border-brand-200 flex items-center space-x-2">
                <span class="font-medium truncate max-w-[120px]" x-text="attachmentName"></span>
                <button type="button" @click="clearFile()" class="text-brand-600 hover:text-rose-600"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <input type="text" x-model="newMessage" placeholder="Type your message to the {{ auth()->user()->isVet() ? 'client' : 'veterinarian' }}..."
                   class="flex-grow rounded-xl border-slate-200 text-xs py-3 px-4 focus:ring-brand-500 focus:border-brand-500 shadow-sm"
                   :disabled="isSending">

            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs px-5 py-3 rounded-xl shadow-md shadow-brand-600/30 transition-all flex items-center space-x-1.5 shrink-0"
                    :disabled="isSending">
                <span>Send</span>
                <i class="fa-solid fa-paper-plane text-xs"></i>
            </button>
        </form>
    </div>

</div>

@push('scripts')
<script>
    function chatComponent(consultationId, userId) {
        return {
            consultationId: consultationId,
            userId: userId,
            messages: [],
            newMessage: '',
            attachmentFile: null,
            attachmentName: '',
            isSending: false,
            pollTimer: null,

            init() {
                this.fetchMessages();
                // Auto poll every 3 seconds
                this.pollTimer = setInterval(() => {
                    this.fetchMessages();
                }, 3000);
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
                    }
                });
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
                document.getElementById('chat-file').value = '';
            },

            sendMessage() {
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
