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
            ->with(['vetAvailabilities', 'vetProfile'])
            ->findOrFail($vetId);

        $pets = Auth::user()->pets()->with('animalType')->get();

        if ($pets->isEmpty()) {
            return redirect()->route('client.pets.create')->with('info', 'Please add a pet to your profile before requesting a consultation.');
        }

        $preselectedPetId = $request->get('pet_id');
        if ($preselectedPetId && !$pets->contains('id', $preselectedPetId)) {
            $preselectedPetId = null;
        }

        $animalsHandled = (array) ($vet->vetProfile->animals_handled ?? []);
        $animalsHandledLower = array_map('strtolower', $animalsHandled);

        // Classify pets into handled/eligible by this veterinarian
        $pets = $pets->map(function ($p) use ($animalsHandledLower) {
            $species = $p->animalType ? $p->animalType->name : '';
            $p->is_handled = in_array(strtolower($species), $animalsHandledLower);
            return $p;
        });

        if (!$preselectedPetId) {
            $firstEligible = $pets->firstWhere('is_handled', true);
            $preselectedPetId = $firstEligible ? $firstEligible->id : $pets->first()->id;
        }

        $baseCreditsCost = (int) SystemSetting::get('booking_credits_cost', 300);
        $addCreditsCost = (int) SystemSetting::get('additional_pet_credits_cost', 150);
        $clientCredits = (int) (Auth::user()->credits ?? 0);

        $baseFee = (float) ($vet->vetProfile->consultation_fee ?? 500.00);
        $addFee = (float) $vet->vetProfile->effective_additional_pet_fee;

        $baseDuration = (int) SystemSetting::get('video_call_time_limit_minutes', 15);
        $addDuration = (int) $vet->vetProfile->effective_additional_pet_duration;

        return view('client.bookings.create', compact(
            'vet', 'pets', 'preselectedPetId', 'animalsHandled',
            'baseCreditsCost', 'addCreditsCost', 'clientCredits',
            'baseFee', 'addFee', 'baseDuration', 'addDuration'
        ));
    }

    public function store(Request $request)
    {
        $vet = User::where('role', 'veterinarian')->where('status', 'active')->with('vetProfile')->findOrFail($request->vet_id);

        $request->validate([
            'vet_id' => 'required|exists:users,id',
            'pet_id' => 'required|exists:pets,id',
            'additional_pet_ids' => 'nullable|array',
            'additional_pet_ids.*' => 'exists:pets,id',
            'type' => 'required|in:chat,video',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'required|string',
            'reason' => 'required|string|min:10',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $animalsHandled = (array) ($vet->vetProfile->animals_handled ?? []);
        $animalsHandledLower = array_map('strtolower', $animalsHandled);

        // Verify primary pet
        $primaryPet = Pet::where('user_id', Auth::id())->with('animalType')->findOrFail($request->pet_id);
        $primaryTypeName = $primaryPet->animalType ? $primaryPet->animalType->name : '';
        if (!in_array(strtolower($primaryTypeName), $animalsHandledLower)) {
            return back()->withInput()->with('error', "Dr. {$vet->name} does not handle {$primaryTypeName}s. Please select an animal category handled by this veterinarian.");
        }

        // Verify additional pets
        $additionalPetIds = array_unique(array_filter((array) $request->input('additional_pet_ids', [])));
        $additionalPetIds = array_values(array_diff($additionalPetIds, [$primaryPet->id]));

        $additionalPets = collect();
        if (!empty($additionalPetIds)) {
            $additionalPets = Pet::where('user_id', Auth::id())
                ->whereIn('id', $additionalPetIds)
                ->with('animalType')
                ->get();

            foreach ($additionalPets as $addPet) {
                $addTypeName = $addPet->animalType ? $addPet->animalType->name : '';
                if (!in_array(strtolower($addTypeName), $animalsHandledLower)) {
                    return back()->withInput()->with('error', "Dr. {$vet->name} does not handle {$addTypeName}s ({$addPet->name}). Please uncheck this pet.");
                }
            }
        }

        $extraPetCount = $additionalPets->count();

        // Calculate pricing, duration, credits
        $baseFee = (float) ($vet->vetProfile->consultation_fee ?? 500.00);
        $addFeePerPet = (float) $vet->vetProfile->effective_additional_pet_fee;
        $totalAdditionalFee = $extraPetCount * $addFeePerPet;
        $totalFee = $baseFee + $totalAdditionalFee;

        $baseDuration = (int) SystemSetting::get('video_call_time_limit_minutes', 15);
        $addDurationPerPet = (int) $vet->vetProfile->effective_additional_pet_duration;
        $totalDuration = $baseDuration + ($extraPetCount * $addDurationPerPet);

        $baseCredits = (int) SystemSetting::get('booking_credits_cost', 300);
        $addCreditsPerPet = (int) SystemSetting::get('additional_pet_credits_cost', 150);
        $totalCredits = $baseCredits + ($extraPetCount * $addCreditsPerPet);

        // Check if client has sufficient credits before requesting
        if (!Auth::user()->hasSufficientCredits($totalCredits)) {
            return back()->withInput()->with('error', "Insufficient credits! You need {$totalCredits} credits to book this consultation ({$baseCredits} base + {$addCreditsPerPet} per extra pet). Your current balance is " . (Auth::user()->credits ?? 0) . " credits.");
        }

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
            'pet_id' => $primaryPet->id,
            'type' => $request->type,
            'status' => 'pending',
            'scheduled_at' => $scheduledAt,
            'fee' => $totalFee,
            'base_fee' => $baseFee,
            'additional_fee' => $totalAdditionalFee,
            'duration_minutes' => $totalDuration,
            'credits_cost' => $totalCredits,
            'credits_deducted' => 0, // Deducted upon booking confirmation
            'reason' => $request->reason,
            'attachments' => $attachmentPaths,
        ]);

        // Attach primary pet and additional pets to pivot table
        $consultation->pets()->attach($primaryPet->id, ['is_primary' => true]);
        foreach ($additionalPets as $addPet) {
            $consultation->pets()->attach($addPet->id, ['is_primary' => false]);
        }

        // Send Notification to Vet with all pet names
        $petNames = $primaryPet->name . ($extraPetCount > 0 ? " and " . $additionalPets->pluck('name')->join(', ') : "");
        AppNotification::create([
            'user_id' => $vet->id,
            'title' => 'New Consultation Request!',
            'message' => Auth::user()->name . ' requested a ' . $request->type . ' consultation for ' . $petNames . ' on ' . $scheduledAt . ' (' . $totalDuration . ' mins).',
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

        $consultation->load(['vet', 'vet.vetProfile', 'pet', 'pets.animalType', 'pets.breed', 'messages.sender', 'record', 'call', 'review']);
        $bookingCreditsCost = $consultation->credits_cost ?: (int) SystemSetting::get('booking_credits_cost', 300);
        $creditsPerMinute = (int) SystemSetting::get('time_extension_credits_per_minute', 5);
        $userCredits = Auth::user()->credits ?? 0;
        $extensionPackages = \App\Models\ConsultationTimeExtension::getPackages();

        return view('client.bookings.show', compact('consultation', 'bookingCreditsCost', 'creditsPerMinute', 'userCredits', 'extensionPackages'));
    }

    public function acceptReschedule(Consultation $consultation)
    {
        if ($consultation->client_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($consultation->status !== 'reschedule_suggested' || !$consultation->suggested_scheduled_at) {
            return back()->with('error', 'No reschedule proposal to accept.');
        }

        $creditsToDeduct = $consultation->credits_cost ?: (int) SystemSetting::get('booking_credits_cost', 300);

        // Deduct credits upon confirmation!
        if (!Auth::user()->hasSufficientCredits($creditsToDeduct)) {
            return back()->with('error', "Insufficient credits! You need {$creditsToDeduct} credits to confirm this rescheduled consultation. Your current balance is " . (Auth::user()->credits ?? 0) . " credits. Please ask admin to add credits.");
        }

        // Deduct client credits
        Auth::user()->deductCredits($creditsToDeduct, $consultation->id, "Confirmation of rescheduled consultation #{$consultation->consultation_number}");

        $newTime = $consultation->suggested_scheduled_at;

        $consultation->update([
            'scheduled_at' => $newTime,
            'suggested_scheduled_at' => null,
            'reschedule_note' => null,
            'status' => 'accepted',
            'credits_deducted' => $creditsToDeduct,
        ]);

        // Send Notification to Vet
        AppNotification::create([
            'user_id' => $consultation->vet_id,
            'title' => 'Rescheduled Consultation Confirmed! 🎉',
            'message' => Auth::user()->name . ' accepted your proposed schedule for ' . $consultation->pet->name . ' on ' . $newTime->format('F d, Y @ g:i A') . '. Session is now confirmed.',
            'type' => 'success',
            'is_read' => false,
        ]);

        return back()->with('success', "You agreed to the new schedule! Consultation confirmed. {$creditsToDeduct} credits have been deducted.");
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
