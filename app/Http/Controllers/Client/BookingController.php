<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\User;
use App\Models\Pet;
use App\Models\AppNotification;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function index()
    {
        $consultations = Consultation::where('client_id', Auth::id())
            ->with(['vet', 'vet.vetProfile', 'pet', 'record'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('client.bookings.index', compact('consultations'));
    }

    public function create(Request $request)
    {
        $vetId = $request->get('vet_id');
        $vet = User::where('role', 'veterinarian')
            ->where('status', 'active')
            ->with('vetAvailabilities')
            ->findOrFail($vetId);

        $pets = Auth::user()->pets;

        if ($pets->isEmpty()) {
            return redirect()->route('client.pets.create')->with('info', 'Please add a pet to your profile before requesting a consultation.');
        }

        $bookingCreditsCost = (int) SystemSetting::get('booking_credits_cost', 300);
        $clientCredits = (int) (Auth::user()->credits ?? 0);

        return view('client.bookings.create', compact('vet', 'pets', 'bookingCreditsCost', 'clientCredits'));
    }

    public function store(Request $request)
    {
        $bookingCreditsCost = (int) SystemSetting::get('booking_credits_cost', 300);

        // Check if client has sufficient credits before requesting
        if (!Auth::user()->hasSufficientCredits($bookingCreditsCost)) {
            return back()->withInput()->with('error', "Insufficient credits! You need at least {$bookingCreditsCost} credits to book a consultation. Your current balance is " . (Auth::user()->credits ?? 0) . " credits. Please contact admin to add credits.");
        }

        $request->validate([
            'vet_id' => 'required|exists:users,id',
            'pet_id' => 'required|exists:pets,id',
            'type' => 'required|in:chat,video',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'required|string',
            'reason' => 'required|string|min:10',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $pet = Pet::where('user_id', Auth::id())->findOrFail($request->pet_id);
        $vet = User::where('role', 'veterinarian')->where('status', 'active')->findOrFail($request->vet_id);

        $scheduledAt = $request->scheduled_date . ' ' . $request->scheduled_time;

        // Concurrency / First-come First-served Real-Time Check:
        // Lock slot if any consultation is pending, accepted, scheduled, in_progress, or reschedule_suggested
        $blockingStatuses = ['pending', 'accepted', 'scheduled', 'in_progress', 'reschedule_suggested'];

        $hasConflict = Consultation::where('vet_id', $vet->id)
            ->where('scheduled_at', $scheduledAt)
            ->whereIn('status', $blockingStatuses)
            ->exists();

        if ($hasConflict) {
            return back()->withInput()->with('error', "This time slot ({$scheduledAt}) was just booked or reserved by another client! Slots are allocated on a first-come, first-served basis. Please pick another slot from the calendar.");
        }

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('consultation_attachments', 'public');
                $attachmentPaths[] = $path;
            }
        }

        $consultation = Consultation::create([
            'consultation_number' => 'VET-' . strtoupper(Str::random(8)),
            'client_id' => Auth::id(),
            'vet_id' => $vet->id,
            'pet_id' => $pet->id,
            'type' => $request->type,
            'status' => 'pending',
            'scheduled_at' => $scheduledAt,
            'fee' => $vet->vetProfile->consultation_fee ?? 500.00,
            'credits_deducted' => 0, // Deducted upon booking confirmation
            'reason' => $request->reason,
            'attachments' => $attachmentPaths,
        ]);

        // Send Notification to Vet
        AppNotification::create([
            'user_id' => $vet->id,
            'title' => 'New Consultation Request!',
            'message' => Auth::user()->name . ' requested a ' . $request->type . ' consultation for ' . $pet->name . ' on ' . $scheduledAt . '.',
            'type' => 'booking',
            'is_read' => false,
        ]);

        return redirect()->route('client.bookings.show', $consultation)->with('success', 'Consultation request submitted! Slot reserved. Awaiting veterinarian confirmation.');
    }

    public function show(Consultation $consultation)
    {
        if ($consultation->client_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $consultation->load(['vet', 'vet.vetProfile', 'pet', 'messages.sender', 'record', 'call', 'review']);
        $bookingCreditsCost = (int) SystemSetting::get('booking_credits_cost', 300);

        return view('client.bookings.show', compact('consultation', 'bookingCreditsCost'));
    }

    public function acceptReschedule(Consultation $consultation)
    {
        if ($consultation->client_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($consultation->status !== 'reschedule_suggested' || !$consultation->suggested_scheduled_at) {
            return back()->with('error', 'No reschedule proposal to accept.');
        }

        $bookingCreditsCost = (int) SystemSetting::get('booking_credits_cost', 300);

        // Deduct credits upon confirmation!
        if (!Auth::user()->hasSufficientCredits($bookingCreditsCost)) {
            return back()->with('error', "Insufficient credits! You need {$bookingCreditsCost} credits to confirm this rescheduled consultation. Your current balance is " . (Auth::user()->credits ?? 0) . " credits. Please ask admin to add credits.");
        }

        // Deduct client credits
        Auth::user()->deductCredits($bookingCreditsCost, $consultation->id, "Confirmation of rescheduled consultation #{$consultation->consultation_number}");

        $newTime = $consultation->suggested_scheduled_at;

        $consultation->update([
            'scheduled_at' => $newTime,
            'suggested_scheduled_at' => null,
            'reschedule_note' => null,
            'status' => 'accepted',
            'credits_deducted' => $bookingCreditsCost,
        ]);

        // Send Notification to Vet
        AppNotification::create([
            'user_id' => $consultation->vet_id,
            'title' => 'Rescheduled Consultation Confirmed! 🎉',
            'message' => Auth::user()->name . ' accepted your proposed schedule for ' . $consultation->pet->name . ' on ' . $newTime->format('F d, Y @ g:i A') . '. Session is now confirmed.',
            'type' => 'success',
            'is_read' => false,
        ]);

        return back()->with('success', "You agreed to the new schedule! Consultation confirmed. {$bookingCreditsCost} credits have been deducted.");
    }

    public function declineReschedule(Consultation $consultation)
    {
        if ($consultation->client_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($consultation->status !== 'reschedule_suggested') {
            return back()->with('error', 'Invalid consultation state.');
        }

        $consultation->update([
            'status' => 'cancelled_by_client',
            'suggested_scheduled_at' => null,
        ]);

        // Send Notification to Vet
        AppNotification::create([
            'user_id' => $consultation->vet_id,
            'title' => 'Reschedule Declined by Client',
            'message' => Auth::user()->name . ' declined the proposed reschedule date and cancelled the request.',
            'type' => 'warning',
            'is_read' => false,
        ]);

        return back()->with('info', 'You declined the proposed schedule. The consultation request has been cancelled without any credit deduction.');
    }

    public function cancel(Consultation $consultation)
    {
        if ($consultation->client_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if (in_array($consultation->status, ['completed', 'cancelled_by_client', 'cancelled_by_vet'])) {
            return back()->with('error', 'This consultation cannot be cancelled.');
        }

        // If credits were already deducted (e.g. accepted booking was cancelled), refund them
        if ($consultation->credits_deducted > 0) {
            $refundAmount = $consultation->credits_deducted;
            Auth::user()->addCredits($refundAmount, "Refund for cancelled consultation #{$consultation->consultation_number}");
        }

        $consultation->update(['status' => 'cancelled_by_client']);

        AppNotification::create([
            'user_id' => $consultation->vet_id,
            'title' => 'Consultation Request Cancelled',
            'message' => Auth::user()->name . ' cancelled their consultation request (' . $consultation->consultation_number . ').',
            'type' => 'warning',
            'is_read' => false,
        ]);

        return back()->with('success', 'Consultation cancelled. Any deducted credits have been refunded.');
    }
}
