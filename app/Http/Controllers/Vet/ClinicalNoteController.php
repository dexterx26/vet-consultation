<?php

namespace App\Http\Controllers\Vet;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationRecord;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ClinicalNoteController extends Controller
{
    public function create(Consultation $consultation)
    {
        if ($consultation->vet_id !== Auth::id()) {
            abort(403);
        }

        $consultation->load([
            'pets.animalType', 'pets.breed',
            'pet.animalType', 'pet.breed',
            'vet.vetProfile',
            'client.clientProfile',
            'record.followUpConsultation'
        ]);

        $record = $consultation->record ?: new ConsultationRecord();
        return view('vet.records.create', compact('consultation', 'record'));
    }

    public function store(Request $request, Consultation $consultation)
    {
        if ($consultation->vet_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'symptoms' => 'required|string',
            'assessment' => 'required|string',
            'recommendations' => 'nullable|string',
            'treatment_advice' => 'nullable|string',
            'medication_info' => 'nullable|string',
            'follow_up_instructions' => 'nullable|string',
            'additional_notes' => 'nullable|string',
            'has_follow_up' => 'nullable|boolean',
            'follow_up_date' => 'nullable|required_if:has_follow_up,1|date|after_or_equal:today',
            'follow_up_time' => 'nullable|string',
            'follow_up_fee' => 'nullable|numeric|min:0',
            'follow_up_type' => 'nullable|in:video,chat',
        ]);

        $vetProfile = Auth::user()->vetProfile;

        // Determine if follow up date was provided
        $hasFollowUp = $request->boolean('has_follow_up') || !empty($request->follow_up_date);
        $followUpDateTime = null;
        $followUpFee = 0.00;

        if ($hasFollowUp && !empty($request->follow_up_date)) {
            $timeString = $request->follow_up_time ?: '10:00';
            $followUpDateTime = Carbon::parse($request->follow_up_date . ' ' . $timeString);

            // Follow up fee defaults to vet profile setting if not explicitly overwritten
            $followUpFee = $request->filled('follow_up_fee')
                ? (float) $request->follow_up_fee
                : (float) ($vetProfile->follow_up_fee ?? 0.00);
        }

        $record = ConsultationRecord::updateOrCreate(
            ['consultation_id' => $consultation->id],
            [
                'symptoms' => $request->symptoms,
                'assessment' => $request->assessment,
                'recommendations' => $request->recommendations,
                'treatment_advice' => $request->treatment_advice,
                'medication_info' => $request->medication_info,
                'follow_up_instructions' => $request->follow_up_instructions,
                'additional_notes' => $request->additional_notes,
                'follow_up_date' => $followUpDateTime,
                'follow_up_fee' => $hasFollowUp ? $followUpFee : 0.00,
            ]
        );

        // Auto-create or sync follow-up teleconsultation for the client
        if ($hasFollowUp && $followUpDateTime) {
            $creditsCost = intval($followUpFee);
            $consultType = $request->follow_up_type ?: ($consultation->type ?: 'video');

            $followUpConsult = null;
            if ($record->follow_up_consultation_id) {
                $followUpConsult = Consultation::find($record->follow_up_consultation_id);
            }

            $reasonText = 'Follow-up checkup for consultation #' . $consultation->consultation_number;
            if ($request->filled('follow_up_instructions')) {
                $reasonText .= ': ' . $request->follow_up_instructions;
            } else {
                $reasonText .= ': Clinical re-evaluation and recovery assessment.';
            }

            if ($followUpConsult && in_array($followUpConsult->status, ['pending', 'accepted', 'scheduled'])) {
                // Update existing follow-up consultation
                $followUpConsult->update([
                    'scheduled_at' => $followUpDateTime,
                    'fee' => $followUpFee,
                    'base_fee' => $followUpFee,
                    'credits_cost' => $creditsCost,
                    'type' => $consultType,
                    'reason' => $reasonText,
                ]);
            } else {
                // Create brand new follow-up teleconsultation pending client approval
                $followUpConsult = Consultation::create([
                    'consultation_number' => 'VET-' . strtoupper(Str::random(8)),
                    'client_id' => $consultation->client_id,
                    'vet_id' => $consultation->vet_id,
                    'pet_id' => $consultation->pet_id,
                    'type' => $consultType,
                    'status' => 'pending',
                    'scheduled_at' => $followUpDateTime,
                    'fee' => $followUpFee,
                    'base_fee' => $followUpFee,
                    'additional_fee' => 0.00,
                    'duration_minutes' => $consultation->duration_minutes ?: 15,
                    'credits_cost' => $creditsCost,
                    'credits_deducted' => 0, // Deducted upon client approval
                    'reason' => $reasonText,
                    'is_follow_up' => true,
                    'parent_consultation_id' => $consultation->id,
                ]);

                // Attach all pets from original consultation
                foreach ($consultation->all_pets as $pet) {
                    $followUpConsult->pets()->attach($pet->id, ['is_primary' => ($pet->id === $consultation->pet_id)]);
                }

                $record->update(['follow_up_consultation_id' => $followUpConsult->id]);
            }

            // Notify client about the scheduled follow-up
            $petNames = $consultation->all_pets->pluck('name')->join(', ');
            $chargeText = $creditsCost > 0
                ? "{$creditsCost} credits (₱" . number_format($followUpFee, 2) . ")"
                : "Free (0 credits)";

            AppNotification::create([
                'user_id' => $consultation->client_id,
                'title' => 'Follow-up Checkup Scheduled! 🗓️',
                'message' => 'Dr. ' . Auth::user()->name . ' scheduled a follow-up checkup for ' . $petNames . ' on ' . $followUpDateTime->format('F d, Y @ g:i A') . ' (' . $chargeText . '). The date is set by your doctor. Please review and approve this appointment.',
                'type' => 'booking',
                'is_read' => false,
            ]);
        }

        if ($consultation->status !== 'completed') {
            $consultation->update(['status' => 'completed']);
        }

        $hasPrescription = !empty($request->medication_info);
        $notifTitle = $hasPrescription ? 'Prescription & Medical Record Ready 📋💊' : 'Clinical Record Available 📋';
        $notifMsg = $hasPrescription 
            ? 'Dr. ' . Auth::user()->name . ' has issued a digital prescription & medical record for ' . ($consultation->pet->name ?? 'your pet') . '.'
            : 'Dr. ' . Auth::user()->name . ' has completed the consultation medical notes for ' . ($consultation->pet->name ?? 'your pet') . '.';

        AppNotification::create([
            'user_id' => $consultation->client_id,
            'title' => $notifTitle,
            'message' => $notifMsg,
            'type' => 'info',
            'is_read' => false,
        ]);

        $successMsg = 'Medical consultation record saved successfully!';
        if ($hasFollowUp && $followUpDateTime) {
            $successMsg .= ' Follow-up consultation scheduled for ' . $followUpDateTime->format('M d, Y @ g:i A') . ' has been sent to client for approval.';
        }

        return redirect()->route('vet.requests.show', $consultation)->with('success', $successMsg);
    }

    public function showPrescription(Consultation $consultation)
    {
        $user = Auth::user();
        if ($consultation->client_id !== $user->id && $consultation->vet_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Unauthorized access to prescription.');
        }

        $record = $consultation->record;
        if (!$record || empty($record->medication_info)) {
            return redirect()->back()->with('warning', 'No prescription has been issued yet for this consultation.');
        }

        $consultation->load([
            'pets.animalType', 'pets.breed',
            'pet.animalType', 'pet.breed',
            'vet.vetProfile',
            'client.clientProfile'
        ]);

        return view('consultation.prescription', compact('consultation', 'record'));
    }
}
