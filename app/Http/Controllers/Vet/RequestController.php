<?php

namespace App\Http\Controllers\Vet;

use App\Http\Controllers\Controller;
use App\Models\AnimalType;
use App\Models\AppNotification;
use App\Models\Consultation;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $query = Consultation::where('vet_id', Auth::id())
            ->with(['client', 'pet', 'pet.animalType', 'pet.breed', 'pets.animalType', 'pets.breed', 'record']);

        // Filter by Date (Scheduled Consultation Date)
        if ($request->filled('date')) {
            $date = $request->date;
            $query->where(function ($q) use ($date) {
                $q->whereDate('scheduled_at', $date)
                  ->orWhereDate('suggested_scheduled_at', $date);
            });
        }

        // Filter by Client Name
        if ($request->filled('client_name')) {
            $clientName = trim($request->client_name);
            $query->whereHas('client', function ($q) use ($clientName) {
                $q->where('name', 'like', '%' . $clientName . '%');
            });
        }

        // Filter by Pet Type (Animal Type)
        if ($request->filled('pet_type')) {
            $petType = $request->pet_type;
            $query->where(function ($q) use ($petType) {
                $q->whereHas('pet', function ($petQ) use ($petType) {
                    if (is_numeric($petType)) {
                        $petQ->where('animal_type_id', $petType);
                    } else {
                        $petQ->whereHas('animalType', function ($atQ) use ($petType) {
                            $atQ->where('name', $petType);
                        });
                    }
                })->orWhereHas('pets', function ($petsQ) use ($petType) {
                    if (is_numeric($petType)) {
                        $petsQ->where('animal_type_id', $petType);
                    } else {
                        $petsQ->whereHas('animalType', function ($atQ) use ($petType) {
                            $atQ->where('name', $petType);
                        });
                    }
                });
            });
        }

        // Filter by Status (optional)
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $consultations = $query->orderBy('created_at', 'desc')->get();

        $animalTypes = AnimalType::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('vet.requests.index', compact('consultations', 'animalTypes'));
    }

    public function show(Consultation $consultation)
    {
        if ($consultation->vet_id !== Auth::id()) {
            abort(403);
        }

        $consultation->load(['client', 'pet', 'pet.animalType', 'pet.breed', 'messages.sender', 'record.followUpConsultation', 'call', 'parentConsultation.record']);
        $bookingCreditsCost = (int) SystemSetting::get('booking_credits_cost', 300);

        return view('vet.requests.show', compact('consultation', 'bookingCreditsCost'));
    }

    public function accept(Consultation $consultation)
    {
        if ($consultation->vet_id !== Auth::id()) {
            abort(403);
        }

        $creditsToDeduct = $consultation->credits_cost ?: (int) SystemSetting::get('booking_credits_cost', 300);
        $client = $consultation->client;

        // Check if client has sufficient credits upon confirmation
        if (!$client->hasSufficientCredits($creditsToDeduct)) {
            return back()->with('error', "Cannot accept booking: Client {$client->name} does not have enough credits ({$client->credits} available, {$creditsToDeduct} required). Client has been notified.");
        }

        // Deduct credits from client
        $client->deductCredits($creditsToDeduct, $consultation->id, "Booking confirmation for #{$consultation->consultation_number}");

        $consultation->update([
            'status' => 'accepted',
            'credits_deducted' => $creditsToDeduct,
        ]);

        AppNotification::create([
            'user_id' => $consultation->client_id,
            'title' => 'Consultation Confirmed! 🎉',
            'message' => 'Dr. ' . Auth::user()->name . ' accepted your consultation request for ' . $consultation->pet->name . '. ' . $creditsToDeduct . ' credits were deducted. You can now message or start your video call at the scheduled time.',
            'type' => 'success',
            'is_read' => false,
        ]);

        return back()->with('success', "Consultation accepted! {$creditsToDeduct} credits deducted from client balance. You can now start communicating with the client.");
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

        // Broadcast real-time decline event to Client via Laravel Reverb
        try {
            broadcast(new \App\Events\ConsultationDeclined($consultation));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Reverb broadcast ConsultationDeclined failed: ' . $e->getMessage());
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Consultation request declined.',
                'consultation_id' => $consultation->id,
            ]);
        }

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
