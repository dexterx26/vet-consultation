<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function showRoom(Consultation $consultation)
    {
        $user = Auth::user();
        $this->authorizeConsultationUser($consultation, $user);

        if (!in_array($consultation->status, ['accepted', 'scheduled', 'in_progress', 'completed'])) {
            return redirect()->back()->with('error', 'Chat room is only available after consultation is accepted.');
        }

        // Mark messages as read
        ConsultationMessage::where('consultation_id', $consultation->id)
            ->where('sender_id', '!=', $user->id)
            ->update(['read_at' => now()]);

        $messages = $consultation->messages()->with('sender')->orderBy('created_at', 'asc')->get();

        return view('consultation.chat', compact('consultation', 'messages', 'user'));
    }

    public function fetchMessages(Consultation $consultation)
    {
        $user = Auth::user();
        $this->authorizeConsultationUser($consultation, $user);

        // Mark unread messages
        ConsultationMessage::where('consultation_id', $consultation->id)
            ->where('sender_id', '!=', $user->id)
            ->update(['read_at' => now()]);

        $messages = $consultation->messages()->with('sender')->orderBy('created_at', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'messages' => $messages->map(function ($msg) use ($user) {
                return [
                    'id' => $msg->id,
                    'sender_name' => $msg->sender->name,
                    'is_me' => $msg->sender_id === $user->id,
                    'message' => $msg->message,
                    'attachment_url' => $msg->attachment_path ? asset('storage/' . $msg->attachment_path) : null,
                    'attachment_type' => $msg->attachment_type,
                    'created_at' => $msg->created_at->format('h:i A'),
                ];
            })
        ]);
    }

    public function sendMessage(Request $request, Consultation $consultation)
    {
        $user = Auth::user();
        $this->authorizeConsultationUser($consultation, $user);

        $request->validate([
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        if (empty($request->message) && !$request->hasFile('attachment')) {
            return response()->json(['error' => 'Message or attachment required.'], 422);
        }

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('chat_attachments', 'public');
            $ext = strtolower($file->getClientOriginalExtension());
            $attachmentType = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']) ? 'image' : 'document';
        }

        $message = ConsultationMessage::create([
            'consultation_id' => $consultation->id,
            'sender_id' => $user->id,
            'message' => $request->message,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
        ]);

        $message->load('sender');

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
            ]
        ]);
    }

    private function authorizeConsultationUser(Consultation $consultation, $user)
    {
        if ($user->isAdmin()) return;
        if ($consultation->client_id !== $user->id && $consultation->vet_id !== $user->id) {
            abort(403, 'Unauthorized room access.');
        }
    }
}
