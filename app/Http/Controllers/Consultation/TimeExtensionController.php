<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationTimeExtension;
use App\Models\ConsultationMessage;
use App\Models\AppNotification;
use App\Models\SystemSetting;
use App\Events\ConsultationTimeUpdated;
use App\Events\ConsultationMessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TimeExtensionController extends Controller
{
    /**
     * Doctor adds extra time free of charge to the customer.
     */
    public function doctorAddTime(Request $request, Consultation $consultation)
    {
        $user = Auth::user();
        if ($consultation->vet_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Only the assigned veterinarian can add complimentary time.');
        }

        $request->validate([
            'minutes' => 'required|integer|min:1|max:120',
        ]);

        $minutes = (int) $request->minutes;

        $sysMsg = null;
        DB::transaction(function () use ($consultation, $user, $minutes, &$sysMsg) {
            $c = Consultation::where('id', $consultation->id)->lockForUpdate()->first();

            $wasClosed = in_array($c->status, ['completed', 'expired']);
            $oldTotalSeconds = ($c->duration_minutes ?: 15) * 60;

            $c->duration_minutes = ($c->duration_minutes ?: 15) + $minutes;
            if ($wasClosed) {
                $c->status = 'in_progress';
                $c->time_consumed_seconds = $oldTotalSeconds;
                $c->last_deducted_at = now();

                if ($c->call && $c->call->status === 'ended') {
                    $c->call->update([
                        'status' => 'waiting',
                        'ended_at' => null,
                    ]);
                }
            }
            $c->save();

            ConsultationTimeExtension::create([
                'consultation_id' => $c->id,
                'requested_by' => 'vet',
                'minutes' => $minutes,
                'credits_cost' => 0,
                'status' => 'approved',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            // Add system message to live chat
            $sysMsg = ConsultationMessage::create([
                'consultation_id' => $c->id,
                'sender_id' => $user->id,
                'message' => "🎉 Dr. {$user->name} added {$minutes} minutes of complimentary consultation time (Free of charge).",
            ]);

            AppNotification::create([
                'user_id' => $c->client_id,
                'title' => 'Complimentary Time Added! 🎁',
                'message' => "Dr. {$user->name} granted +{$minutes} minutes of free extra consultation time for your appointment.",
                'type' => 'success',
                'is_read' => false,
            ]);
        });

        $consultation->refresh();

        try {
            if ($sysMsg) {
                $sysMsg->load('sender');
                broadcast(new ConsultationMessageSent($sysMsg))->toOthers();
            }
            broadcast(new ConsultationTimeUpdated(
                $consultation->id,
                'free_time_added',
                null,
                [
                    'duration_minutes' => $consultation->duration_minutes,
                    'remaining_seconds' => $consultation->remaining_seconds,
                    'formatted_remaining' => $consultation->formatted_remaining_time,
                    'total_seconds' => $consultation->total_duration_seconds,
                ],
                (int) ($consultation->client->credits ?? 0)
            ))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Reverb broadcast doctorAddTime failed: ' . $e->getMessage());
        }

        if (!$request->wantsJson() && !$request->ajax()) {
            return back()->with('success', "Successfully added {$minutes} free minutes to the consultation.");
        }

        return response()->json([
            'status' => 'success',
            'message' => "Successfully added {$minutes} free minutes to the consultation.",
            'duration_minutes' => $consultation->duration_minutes,
            'remaining_seconds' => $consultation->remaining_seconds,
            'formatted_remaining' => $consultation->formatted_remaining_time,
            'total_seconds' => $consultation->total_duration_seconds,
        ]);
    }

    /**
     * Client requests more time (requires credits & vet approval).
     */
    public function requestExtension(Request $request, Consultation $consultation)
    {
        $user = Auth::user();
        if ($consultation->client_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Only the client can request paid time extensions.');
        }

        $request->validate([
            'minutes' => 'required|integer|min:1|max:300',
        ]);

        // Check if there is already an active pending request
        $existing = ConsultationTimeExtension::where('consultation_id', $consultation->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            $msg = "An extension request for +{$existing->minutes} minutes is already pending doctor review.";
            if (!$request->wantsJson() && !$request->ajax()) {
                return back()->with('error', $msg);
            }
            return response()->json(['error' => $msg], 422);
        }

        $minutes = (int) $request->minutes;
        $cost = ConsultationTimeExtension::getCostForMinutes($minutes);

        $client = $consultation->client;
        if (!$client->hasSufficientCredits($cost)) {
            $msg = "Insufficient credits. You need {$cost} credits for +{$minutes} minutes, but only have {$client->credits} credits available.";
            if (!$request->wantsJson() && !$request->ajax()) {
                return back()->with('error', $msg);
            }
            return response()->json(['error' => $msg], 422);
        }

        $extension = ConsultationTimeExtension::create([
            'consultation_id' => $consultation->id,
            'requested_by' => 'client',
            'minutes' => $minutes,
            'credits_cost' => $cost,
            'status' => 'pending',
        ]);

        // Add notice to chat
        $sysMsg = ConsultationMessage::create([
            'consultation_id' => $consultation->id,
            'sender_id' => $user->id,
            'message' => "⏱️ Client {$user->name} requested a +{$minutes} minute extension ({$cost} credits) awaiting veterinarian approval.",
        ]);

        AppNotification::create([
            'user_id' => $consultation->vet_id,
            'title' => 'Client Requested Time Extension ⏱️',
            'message' => "{$user->name} requested +{$minutes} minutes extension ({$cost} credits). Please approve or decline.",
            'type' => 'info',
            'is_read' => false,
        ]);

        try {
            if ($sysMsg) {
                $sysMsg->load('sender');
                broadcast(new ConsultationMessageSent($sysMsg))->toOthers();
            }
            broadcast(new ConsultationTimeUpdated(
                $consultation->id,
                'requested',
                [
                    'id' => $extension->id,
                    'minutes' => $extension->minutes,
                    'credits_cost' => $extension->credits_cost,
                    'status' => $extension->status,
                ],
                null,
                (int) ($client->credits ?? 0)
            ))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Reverb broadcast requestExtension failed: ' . $e->getMessage());
        }

        if (!$request->wantsJson() && !$request->ajax()) {
            return back()->with('success', "Extension request for +{$minutes} minutes submitted. Awaiting veterinarian approval.");
        }

        return response()->json([
            'status' => 'success',
            'message' => "Extension request for +{$minutes} minutes submitted. Awaiting veterinarian approval.",
            'extension' => [
                'id' => $extension->id,
                'minutes' => $extension->minutes,
                'credits_cost' => $extension->credits_cost,
                'status' => $extension->status,
            ]
        ]);
    }

    /**
     * Veterinarian approves the client's paid time extension.
     */
    public function approveExtension(Request $request, Consultation $consultation, ConsultationTimeExtension $extension)
    {
        $user = Auth::user();
        if ($consultation->vet_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Only the assigned veterinarian can approve time extensions.');
        }

        if ($extension->consultation_id !== $consultation->id || $extension->status !== 'pending') {
            $msg = 'This extension request is no longer pending.';
            if (!$request->wantsJson() && !$request->ajax()) {
                return back()->with('error', $msg);
            }
            return response()->json(['error' => $msg], 422);
        }

        $client = $consultation->client;

        if (!$client->hasSufficientCredits($extension->credits_cost)) {
            $msg = "Cannot approve: Client {$client->name} no longer has sufficient credits ({$client->credits} available, {$extension->credits_cost} required).";
            if (!$request->wantsJson() && !$request->ajax()) {
                return back()->with('error', $msg);
            }
            return response()->json(['error' => $msg], 422);
        }

        $sysMsg = null;
        DB::transaction(function () use ($consultation, $extension, $client, $user, &$sysMsg) {
            $c = Consultation::where('id', $consultation->id)->lockForUpdate()->first();

            // Deduct credits from client
            $client->deductCredits(
                $extension->credits_cost,
                $c->id,
                "Time extension (+{$extension->minutes} mins) for #{$c->consultation_number}"
            );

            $wasClosed = in_array($c->status, ['completed', 'expired']);
            $oldTotalSeconds = ($c->duration_minutes ?: 15) * 60;

            // Extend consultation duration
            $c->duration_minutes = ($c->duration_minutes ?: 15) + $extension->minutes;
            $c->credits_deducted = ($c->credits_deducted ?: 0) + $extension->credits_cost;

            if ($wasClosed) {
                $c->status = 'in_progress';
                // When reopening an ended/completed consultation, ensure time consumed starts from old total duration,
                // so remaining time starts fresh from 0 + selected additional minutes ($extension->minutes * 60)
                $c->time_consumed_seconds = $oldTotalSeconds;
                $c->last_deducted_at = now();

                if ($c->call && $c->call->status === 'ended') {
                    $c->call->update([
                        'status' => 'waiting',
                        'ended_at' => null,
                    ]);
                }
            }
            $c->save();

            // Mark extension approved
            $extension->update([
                'status' => 'approved',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            // Add system message to live chat
            $sysMsg = ConsultationMessage::create([
                'consultation_id' => $c->id,
                'sender_id' => $user->id,
                'message' => "✅ Dr. {$user->name} approved the +{$extension->minutes} minute extension ({$extension->credits_cost} credits deducted).",
            ]);

            AppNotification::create([
                'user_id' => $c->client_id,
                'title' => 'Time Extension Approved! 🎉',
                'message' => "Dr. {$user->name} approved your +{$extension->minutes} minutes extension. {$extension->credits_cost} credits were deducted.",
                'type' => 'success',
                'is_read' => false,
            ]);
        });

        $consultation->refresh();
        $updatedCredits = (int) ($client->fresh()->credits ?? 0);

        try {
            if ($sysMsg) {
                $sysMsg->load('sender');
                broadcast(new ConsultationMessageSent($sysMsg))->toOthers();
            }
            broadcast(new ConsultationTimeUpdated(
                $consultation->id,
                'approved',
                null,
                [
                    'duration_minutes' => $consultation->duration_minutes,
                    'remaining_seconds' => $consultation->remaining_seconds,
                    'formatted_remaining' => $consultation->formatted_remaining_time,
                    'total_seconds' => $consultation->total_duration_seconds,
                ],
                $updatedCredits
            ))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Reverb broadcast approveExtension failed: ' . $e->getMessage());
        }

        if (!$request->wantsJson() && !$request->ajax()) {
            return back()->with('success', "Approved +{$extension->minutes} minutes extension. Client credits deducted.");
        }

        return response()->json([
            'status' => 'success',
            'message' => "Approved +{$extension->minutes} minutes extension. Client credits deducted.",
            'duration_minutes' => $consultation->duration_minutes,
            'remaining_seconds' => $consultation->remaining_seconds,
            'formatted_remaining' => $consultation->formatted_remaining_time,
            'total_seconds' => $consultation->total_duration_seconds,
            'client_credits' => (int) ($client->fresh()->credits ?? 0),
        ]);
    }

    /**
     * Veterinarian declines the client's time extension.
     */
    public function declineExtension(Request $request, Consultation $consultation, ConsultationTimeExtension $extension)
    {
        $user = Auth::user();
        if ($consultation->vet_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Only the assigned veterinarian can decline time extensions.');
        }

        if ($extension->consultation_id !== $consultation->id || $extension->status !== 'pending') {
            $msg = 'This extension request is no longer pending.';
            if (!$request->wantsJson() && !$request->ajax()) {
                return back()->with('error', $msg);
            }
            return response()->json(['error' => $msg], 422);
        }

        $extension->update([
            'status' => 'declined',
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'decline_reason' => $request->reason,
        ]);

        $sysMsg = ConsultationMessage::create([
            'consultation_id' => $consultation->id,
            'sender_id' => $user->id,
            'message' => "❌ Dr. {$user->name} declined the time extension request.",
        ]);

        AppNotification::create([
            'user_id' => $consultation->client_id,
            'title' => 'Extension Request Declined',
            'message' => "Dr. {$user->name} was unable to extend the consultation time.",
            'type' => 'warning',
            'is_read' => false,
        ]);

        try {
            if ($sysMsg) {
                $sysMsg->load('sender');
                broadcast(new ConsultationMessageSent($sysMsg))->toOthers();
            }
            broadcast(new ConsultationTimeUpdated(
                $consultation->id,
                'declined',
                null,
                null,
                null
            ))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Reverb broadcast declineExtension failed: ' . $e->getMessage());
        }

        if (!$request->wantsJson() && !$request->ajax()) {
            return back()->with('success', 'Time extension request declined. No credits were deducted.');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Time extension request declined. No credits were deducted.',
        ]);
    }

    /**
     * Client cancels their own pending extension request.
     */
    public function cancelExtension(Request $request, Consultation $consultation, ConsultationTimeExtension $extension)
    {
        $user = Auth::user();
        if ($consultation->client_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        if ($extension->consultation_id !== $consultation->id || $extension->status !== 'pending') {
            $msg = 'This extension request is not pending.';
            if (!$request->wantsJson() && !$request->ajax()) {
                return back()->with('error', $msg);
            }
            return response()->json(['error' => $msg], 422);
        }

        $extension->update(['status' => 'cancelled']);

        try {
            broadcast(new ConsultationTimeUpdated(
                $consultation->id,
                'cancelled',
                null,
                null,
                null
            ))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Reverb broadcast cancelExtension failed: ' . $e->getMessage());
        }

        if (!$request->wantsJson() && !$request->ajax()) {
            return back()->with('success', 'Extension request cancelled.');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Extension request cancelled.',
        ]);
    }
}
