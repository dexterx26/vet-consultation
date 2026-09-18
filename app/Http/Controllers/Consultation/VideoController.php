<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationCall;
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

        if ($consultation->status === 'accepted') {
            $consultation->update(['status' => 'in_progress']);
        }

        $isVet = ($user->id === $consultation->vet_id);

        $timeLimitMinutes = (int) \App\Models\SystemSetting::get('video_call_time_limit_minutes', 1);
        $timeLimitSeconds = $timeLimitMinutes * 60;

        return view('consultation.video', compact('consultation', 'call', 'user', 'isVet', 'timeLimitMinutes', 'timeLimitSeconds'));
    }

    public function endCall(Consultation $consultation)
    {
        $user = Auth::user();
        if ($consultation->client_id !== $user->id && $consultation->vet_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        if ($call = $consultation->call) {
            $call->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);
        }

        if ($user->isVet()) {
            return redirect()->route('vet.records.create', $consultation)->with('info', 'Video call ended. Please fill out the consultation clinical record.');
        }

        return redirect()->route('client.bookings.show', $consultation)->with('info', 'Video call ended.');
    }
}
