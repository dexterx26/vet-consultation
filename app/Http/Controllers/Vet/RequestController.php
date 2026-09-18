<?php

namespace App\Http\Controllers\Vet;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\AppNotification;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    public function index()
    {
        $consultations = Consultation::where('vet_id', Auth::id())
            ->with(['client', 'pet', 'pet.animalType', 'pet.breed', 'record'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('vet.requests.index', compact('consultations'));
    }

    public function show(Consultation $consultation)
    {
        if ($consultation->vet_id !== Auth::id()) {
            abort(403);
        }

        $consultation->load(['client', 'pet', 'pet.animalType', 'pet.breed', 'messages.sender', 'record', 'call']);
        $bookingCreditsCost = (int) SystemSetting::get('booking_credits_cost', 300);

        return view('vet.requests.show', compact('consultation', 'bookingCreditsCost'));
    }

    public function accept(Consultation $consultation)
    {
        if ($consultation->vet_id !== Auth::id()) {
            abort(403);
        }

        $bookingCreditsCost = (int) SystemSetting::get('booking_credits_cost', 300);
        $client = $consultation->client;

        // Check if client has sufficient credits upon confirmation
        if (!$client->hasSufficientCredits($bookingCreditsCost)) {
            return back()->with('error', "Cannot accept booking: Client {$client->name} does not have enough credits ({$client->credits} available, {$bookingCreditsCost} required). Client has been notified.");
        }

        // Deduct credits from client
        $client->deductCredits($bookingCreditsCost, $consultation->id, "Booking confirmation for #{$consultation->consultation_number}");

        $consultation->update([
            'status' => 'accepted',
            'credits_deducted' => $bookingCreditsCost,
        ]);

        AppNotification::create([
            'user_id' => $consultation->client_id,
            'title' => 'Consultation Confirmed! 🎉',
            'message' => 'Dr. ' . Auth::user()->name . ' accepted your consultation request for ' . $consultation->pet->name . '. ' . $bookingCreditsCost . ' credits were deducted. You can now message or start your video call at the scheduled time.',
            'type' => 'success',
            'is_read' => false,
        ]);

        return back()->with('success', "Consultation accepted! {$bookingCreditsCost} credits deducted from client balance. You can now start communicating with the client.");
    }

    public function suggestReschedule(Request $request, Consultation $consultation)
    {
        if ($consultation->vet_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'suggested_date' => 'required|date|after_or_equal:today',
            'suggested_time' => 'required|string',
            'reschedule_note' => 'required|string|max:500',
        ]);

        $suggestedScheduledAt = $request->suggested_date . ' ' . $request->suggested_time;

        // Check if the vet already has another booking at this suggested slot
        $conflict = Consultation::where('vet_id', Auth::id())
            ->where('id', '!=', $consultation->id)
            ->where('scheduled_at', $suggestedScheduledAt)
            ->whereIn('status', ['pending', 'accepted', 'scheduled', 'in_progress'])
            ->exists();

        if ($conflict) {
            return back()->with('error', 'You already have another active appointment at that proposed time slot. Please choose a different time.');
        }

        $consultation->update([
            'status' => 'reschedule_suggested',
            'suggested_scheduled_at' => $suggestedScheduledAt,
            'reschedule_note' => $request->reschedule_note,
        ]);

        AppNotification::create([
            'user_id' => $consultation->client_id,
            'title' => 'Doctor Suggested New Time Slot 📅',
            'message' => 'Dr. ' . Auth::user()->name . ' suggested a new consultation time: ' . \Carbon\Carbon::parse($suggestedScheduledAt)->format('F d, Y @ g:i A') . '. Note: "' . $request->reschedule_note . '". Please confirm if you agree.',
            'type' => 'warning',
            'is_read' => false,
        ]);

        return back()->with('success', 'New time slot proposal sent to client! Waiting for client confirmation.');
    }

    public function decline(Request $request, Consultation $consultation)
    {
        if ($consultation->vet_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'decline_reason' => 'required|string|max:500',
        ]);

        $consultation->update([
            'status' => 'declined',
            'decline_reason' => $request->decline_reason,
        ]);

        AppNotification::create([
            'user_id' => $consultation->client_id,
            'title' => 'Consultation Request Declined',
            'message' => 'Dr. ' . Auth::user()->name . ' was unable to accept your request. Reason: ' . $request->decline_reason,
            'type' => 'warning',
            'is_read' => false,
        ]);

        return back()->with('info', 'Consultation request declined.');
    }

    public function markCompleted(Consultation $consultation)
    {
        if ($consultation->vet_id !== Auth::id()) {
            abort(403);
        }

        $consultation->update(['status' => 'completed']);

        AppNotification::create([
            'user_id' => $consultation->client_id,
            'title' => 'Consultation Completed',
            'message' => 'Your consultation with Dr. ' . Auth::user()->name . ' has been marked as completed. Please check your medical records & leave a review!',
            'type' => 'info',
            'is_read' => false,
        ]);

        return redirect()->route('vet.records.create', $consultation)->with('success', 'Consultation completed! Please fill out the medical notes for the client.');
    }
}
