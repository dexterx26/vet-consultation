<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationCall;
use App\Models\ConsultationMessage;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    public function showRoom(Consultation $consultation)
    {
        $user = Auth::user();
        if ($consultation->client_id !== $user->id && $consultation->vet_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Unauthorized access to video room.');
        }

        if (!in_array($consultation->status, ['accepted', 'scheduled', 'in_progress', 'completed'])) {
            return redirect()->back()->with('error', 'Video call is only available after booking acceptance.');
        }

        // Create or get call session
        $call = ConsultationCall::firstOrCreate(
            ['consultation_id' => $consultation->id],
            [
                'room_name' => 'room_' . $consultation->consultation_number,
                'room_token' => Str::random(32),
                'status' => 'waiting',
            ]
        );

        if ($call->status === 'ended' && $consultation->status === 'in_progress') {
            $call->update([
                'status' => 'waiting',
                'ended_at' => null,
            ]);
        }

        if ($consultation->status === 'accepted') {
            $consultation->update(['status' => 'in_progress']);
        }

        $isVet = ($user->id === $consultation->vet_id);

        $consultation->load(['pets.animalType', 'pet.animalType', 'vet', 'client']);

        if ($consultation->isExpired() && !in_array($consultation->status, ['completed'])) {
            $consultation->update(['status' => 'completed']);
        }

        // Process heartbeat upon entering video room
        $timer = TimeSyncController::processHeartbeat($consultation, $user);

        $timeLimitMinutes = $consultation->duration_minutes ?: (int) \App\Models\SystemSetting::get('video_call_time_limit_minutes', 15);
        $totalDurationSeconds = $consultation->total_duration_seconds;
        $remainingSeconds = $consultation->remaining_seconds;
        $consumedSeconds = $consultation->time_consumed_seconds ?? 0;
        $creditsPerMinute = (int) \App\Models\SystemSetting::get('time_extension_credits_per_minute', 5);
        $userCredits = $user->credits ?? 0;
        $extensionPackages = \App\Models\ConsultationTimeExtension::getPackages();

        return view('consultation.video', compact(
            'consultation',
            'call',
            'user',
            'isVet',
            'timer',
            'timeLimitMinutes',
            'totalDurationSeconds',
            'remainingSeconds',
            'consumedSeconds',
            'creditsPerMinute',
            'userCredits',
            'extensionPackages'
        ));
    }

    public function endCall(Request $request, Consultation $consultation)
    {
        $user = Auth::user();
        if ($consultation->client_id !== $user->id && $consultation->vet_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

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

        // Post system message
        ConsultationMessage::create([
            'consultation_id' => $consultation->id,
            'sender_id' => $user->id,
            'message' => "🛑 Video consultation ended by {$senderName}. Teleconsultation is now completed.",
        ]);

        $otherUserId = ($user->id === $consultation->vet_id) ? $consultation->client_id : $consultation->vet_id;
        AppNotification::create([
            'user_id' => $otherUserId,
            'title' => 'Consultation Ended',
            'message' => "{$senderName} has ended the video consultation.",
            'type' => 'info',
            'is_read' => false,
        ]);

        if ($isVet) {
            if ($request->input('redirect_to') === 'summary') {
                return redirect()->route('vet.requests.show', $consultation)->with('info', 'Video call ended.');
            }
            return redirect()->route('vet.records.create', $consultation)->with('info', 'Video call ended. Please issue the prescription and complete the clinical record.');
        }

        return redirect()->route('client.bookings.show', $consultation)->with('info', 'Video call ended.');
    }
}
