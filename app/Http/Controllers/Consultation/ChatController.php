<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationMessage;
use App\Models\AppNotification;
use App\Events\ConsultationMessageSent;
use App\Events\ConsultationMessageRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\MediaCompressionService;

class ChatController extends Controller
{
    public function showRoom(Consultation $consultation)
    {
        $user = Auth::user();
        $this->authorizeConsultationUser($consultation, $user);

        if (!in_array($consultation->status, ['accepted', 'scheduled', 'in_progress', 'completed'])) {
            return redirect()->back()->with('error', 'Chat room is only available after consultation is accepted.');
        }

        $consultation->load(['pets.animalType', 'pet.animalType', 'vet', 'client']);

        // Process heartbeat upon entering chat room
        $timer = TimeSyncController::processHeartbeat($consultation, $user);

        // Mark messages as read
        $readCount = ConsultationMessage::where('consultation_id', $consultation->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($readCount > 0) {
            try {
                broadcast(new ConsultationMessageRead($consultation->id, $user->id, now()->format('h:i A')))->toOthers();
            } catch (\Throwable $e) {
                Log::warning('Reverb broadcast read failed: ' . $e->getMessage());
            }
        }

        $messages = $consultation->messages()->with('sender')->orderBy('created_at', 'asc')->get();
        $formattedMessages = $messages->map(function ($msg) use ($user) {
            return [
                'id' => $msg->id,
                'sender_name' => $msg->sender->name,
                'is_me' => $msg->sender_id === $user->id,
                'message' => $msg->message,
                'attachment_url' => $msg->attachment_path ? asset('storage/' . $msg->attachment_path) : null,
                'attachment_type' => $msg->attachment_type,
                'created_at' => $msg->created_at->format('h:i A'),
                'is_read' => !is_null($msg->read_at),
                'read_at' => $msg->read_at ? $msg->read_at->format('h:i A') : null,
            ];
        });

        $isVet = ($user->id === $consultation->vet_id);
        $creditsPerMinute = (int) \App\Models\SystemSetting::get('time_extension_credits_per_minute', 5);
        $userCredits = $user->credits ?? 0;
        $extensionPackages = \App\Models\ConsultationTimeExtension::getPackages();

        return view('consultation.chat', compact('consultation', 'messages', 'formattedMessages', 'user', 'isVet', 'timer', 'creditsPerMinute', 'userCredits', 'extensionPackages'));
    }

    public function fetchMessages(Consultation $consultation)
    {
        $user = Auth::user();
        $this->authorizeConsultationUser($consultation, $user);

        // Process heartbeat to track presence and deduct active doctor time
        $timer = TimeSyncController::processHeartbeat($consultation, $user);

        // Mark unread messages
        $readCount = ConsultationMessage::where('consultation_id', $consultation->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($readCount > 0) {
            try {
                broadcast(new ConsultationMessageRead($consultation->id, $user->id, now()->format('h:i A')))->toOthers();
            } catch (\Throwable $e) {
                Log::warning('Reverb broadcast read failed: ' . $e->getMessage());
            }
        }

        $messages = $consultation->messages()->with('sender')->orderBy('created_at', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'timer' => $timer,
            'messages' => $messages->map(function ($msg) use ($user) {
                return [
                    'id' => $msg->id,
                    'sender_name' => $msg->sender->name,
                    'is_me' => $msg->sender_id === $user->id,
                    'message' => $msg->message,
                    'attachment_url' => $msg->attachment_path ? asset('storage/' . $msg->attachment_path) : null,
                    'attachment_type' => $msg->attachment_type,
                    'created_at' => $msg->created_at->format('h:i A'),
                    'is_read' => !is_null($msg->read_at),
                    'read_at' => $msg->read_at ? $msg->read_at->format('h:i A') : null,
                ];
            })
        ]);
    }

    public function markAsRead(Consultation $consultation)
    {
        $user = Auth::user();
        $this->authorizeConsultationUser($consultation, $user);

        $now = now();
        $updatedCount = ConsultationMessage::where('consultation_id', $consultation->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => $now]);

        if ($updatedCount > 0) {
            try {
                broadcast(new ConsultationMessageRead($consultation->id, $user->id, $now->format('h:i A')))->toOthers();
            } catch (\Throwable $e) {
                Log::warning('Reverb broadcast read failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'updated_count' => $updatedCount,
            'read_at' => $now->format('h:i A')
        ]);
    }

    public function sendMessage(Request $request, Consultation $consultation)
    {
        $user = Auth::user();
        $this->authorizeConsultationUser($consultation, $user);

        if ($consultation->isExpired() || in_array($consultation->status, ['completed', 'expired', 'declined', 'cancelled_by_client', 'cancelled_by_vet'])) {
            return response()->json(['error' => 'Consultation time limit has expired. No further messages can be sent.'], 403);
        }

        $request->validate([
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,mp4,mov,webm,avi,mkv,ogv,m4v,3gp|max:51200',
        ]);

        if (empty($request->message) && !$request->hasFile('attachment')) {
            return response()->json(['error' => 'Message or attachment required.'], 422);
        }

        // Process heartbeat on active interaction
        TimeSyncController::processHeartbeat($consultation, $user);

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('chat_attachments', 'public');
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $mime = strtolower($file->getMimeType() ?: '');
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            $videoExtensions = ['mp4', 'mov', 'webm', 'avi', 'mkv', 'ogv', 'm4v', '3gp'];

            if (str_starts_with($mime, 'image/') || in_array($ext, $imageExtensions)) {
                $attachmentType = 'image';
            } elseif (str_starts_with($mime, 'video/') || in_array($ext, $videoExtensions)) {
                $attachmentType = 'video';
            } else {
                $attachmentType = 'document';
            }

            // Compress media (images and videos) to save disk space and optimize web playback
            if (in_array($attachmentType, ['image', 'video'])) {
                $fullPath = Storage::disk('public')->path($attachmentPath);
                $compressionResult = MediaCompressionService::compress($fullPath, $attachmentType);
                if (!empty($compressionResult['path']) && $compressionResult['path'] !== $fullPath) {
                    $attachmentPath = 'chat_attachments/' . basename($compressionResult['path']);
                    $attachmentType = 'video';
                }
            }
        }

        $message = ConsultationMessage::create([
            'consultation_id' => $consultation->id,
            'sender_id' => $user->id,
            'message' => $request->message,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
        ]);

        $message->load('sender');

        try {
            broadcast(new ConsultationMessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Reverb broadcast message failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $message->id,
                'sender_name' => $message->sender->name,
                'is_me' => true,
                'message' => $message->message,
                'attachment_url' => $attachmentPath ? asset('storage/' . $attachmentPath) : null,
                'attachment_type' => $attachmentType,
                'created_at' => $message->created_at->format('h:i A'),
                'is_read' => false,
                'read_at' => null,
            ]
        ]);
    }

    public function endChat(Request $request, Consultation $consultation)
    {
        $user = Auth::user();
        $this->authorizeConsultationUser($consultation, $user);

        $actualDuration = $consultation->time_consumed_seconds ?? 0;
        if ($call = $consultation->call) {
            $call->update([
                'status' => 'ended',
                'ended_at' => now(),
                'duration_seconds' => $actualDuration,
            ]);
        }

        $isVet = ($user->id === $consultation->vet_id || $user->isVet() || $user->isAdmin());
        if ($isVet) {
            $consultation->time_consumed_seconds = $consultation->total_duration_seconds;
        }

        if (!in_array($consultation->status, ['completed', 'cancelled_by_client', 'cancelled_by_vet', 'declined'])) {
            $consultation->status = 'completed';
        }
        $consultation->save();

        $senderName = $isVet ? "Dr. {$user->name}" : $user->name;

        // Post a system message in the chat
        $sysMsg = ConsultationMessage::create([
            'consultation_id' => $consultation->id,
            'sender_id' => $user->id,
            'message' => "🛑 Consultation ended by {$senderName}. Teleconsultation is now completed.",
        ]);
        $sysMsg->load('sender');

        try {
            broadcast(new ConsultationMessageSent($sysMsg))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Reverb broadcast end message failed: ' . $e->getMessage());
        }

        $otherUserId = ($user->id === $consultation->vet_id) ? $consultation->client_id : $consultation->vet_id;
        AppNotification::create([
            'user_id' => $otherUserId,
            'title' => 'Consultation Ended',
            'message' => "{$senderName} has ended the teleconsultation.",
            'type' => 'info',
            'is_read' => false,
        ]);

        if ($isVet) {
            if ($request->input('redirect_to') === 'summary') {
                return redirect()->route('vet.requests.show', $consultation)->with('info', 'Chat consultation ended.');
            }
            return redirect()->route('vet.records.create', $consultation)->with('info', 'Chat consultation ended. Please issue the prescription and complete the clinical record.');
        }

        return redirect()->route('client.bookings.show', $consultation)->with('info', 'Chat consultation ended.');
    }

    private function authorizeConsultationUser(Consultation $consultation, $user)
    {
        if ($user->isAdmin()) return;
        if ($consultation->client_id !== $user->id && $consultation->vet_id !== $user->id) {
            abort(403, 'Unauthorized room access.');
        }
    }
}
