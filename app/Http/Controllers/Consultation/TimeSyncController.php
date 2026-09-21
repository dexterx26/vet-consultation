<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimeSyncController extends Controller
{
    public function sync(Request $request, Consultation $consultation)
    {
        $user = Auth::user();
        if ($consultation->client_id !== $user->id && $consultation->vet_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Unauthorized access to consultation.');
        }

        $timerData = self::processHeartbeat($consultation, $user);

        return response()->json([
            'status' => 'success',
            'timer' => $timerData,
        ]);
    }

    public static function processHeartbeat(Consultation $consultation, User $user): array
    {
        $isVet = ($user->id === $consultation->vet_id);
        $now = now();

        DB::transaction(function () use ($consultation, $isVet, $now) {
            $c = Consultation::where('id', $consultation->id)->lockForUpdate()->first();
            if (!$c) return;

            if ($isVet) {
                // Doctor entered / active
                if (!$c->doctor_joined_at) {
                    $c->doctor_joined_at = $now;
                }
                if (in_array($c->status, ['accepted', 'scheduled'])) {
                    $c->status = 'in_progress';
                }

                // Deduct time strictly when doctor is active
                if ($c->last_deducted_at !== null) {
                    $secondsSinceLastDeduction = (int) abs($now->diffInSeconds($c->last_deducted_at));
                    
                    // Interval bounded between 1 and 10 seconds (standard heartbeat every 3 seconds)
                    if ($secondsSinceLastDeduction >= 1 && $secondsSinceLastDeduction <= 10) {
                        $totalSecs = ($c->duration_minutes ?: 15) * 60;
                        $newConsumed = min($totalSecs, ($c->time_consumed_seconds ?? 0) + $secondsSinceLastDeduction);
                        $c->time_consumed_seconds = $newConsumed;
                        $c->last_deducted_at = $now;

                        if ($newConsumed >= $totalSecs && $c->status === 'in_progress') {
                            $c->status = 'completed';
                        }
                    } elseif ($secondsSinceLastDeduction > 10) {
                        // Re-entered after disconnect / being offline, resume interval without counting gap
                        $c->last_deducted_at = $now;
                    }
                } else {
                    // First heartbeat of active doctor session
                    $c->last_deducted_at = $now;
                }

                $c->doctor_last_seen_at = $now;
            } else {
                // Client entered / active - do NOT deduct time
                if (!$c->client_joined_at) {
                    $c->client_joined_at = $now;
                }
                $c->client_last_seen_at = $now;
            }

            $c->save();
        });

        $consultation->refresh();

        $pendingExtension = $consultation->pendingTimeExtension;
        $pendingExtensionData = $pendingExtension ? [
            'id' => $pendingExtension->id,
            'requested_by' => $pendingExtension->requested_by,
            'minutes' => $pendingExtension->minutes,
            'credits_cost' => $pendingExtension->credits_cost,
            'created_at' => $pendingExtension->created_at->format('h:i A'),
        ] : null;

        return [
            'total_seconds' => $consultation->total_duration_seconds,
            'consumed_seconds' => $consultation->time_consumed_seconds ?? 0,
            'remaining_seconds' => $consultation->remaining_seconds,
            'formatted_remaining' => $consultation->formatted_remaining_time,
            'formatted_consumed' => $consultation->formatted_consumed_time,
            'doctor_present' => $consultation->isDoctorPresent(),
            'client_present' => $consultation->isClientPresent(),
            'is_timer_running' => $consultation->isTimerRunning(),
            'is_expired' => $consultation->isExpired(),
            'consultation_status' => $consultation->status,
            'pending_extension' => $pendingExtensionData,
            'client_credits' => (int) ($consultation->client->credits ?? 0),
        ];
    }
}
