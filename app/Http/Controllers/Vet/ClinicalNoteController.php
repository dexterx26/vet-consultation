<?php

namespace App\Http\Controllers\Vet;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationRecord;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClinicalNoteController extends Controller
{
    public function create(Consultation $consultation)
    {
        if ($consultation->vet_id !== Auth::id()) {
            abort(403);
        }

        $consultation->load(['pets.animalType', 'pets.breed', 'pet.animalType', 'pet.breed', 'vet.vetProfile', 'client.clientProfile']);
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
        ]);

        $record = ConsultationRecord::updateOrCreate(
            ['consultation_id' => $consultation->id],
            $request->only([
                'symptoms', 'assessment', 'recommendations', 'treatment_advice',
                'medication_info', 'follow_up_instructions', 'additional_notes'
            ])
        );

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

        $successMsg = $hasPrescription 
            ? 'Medical consultation record and digital prescription saved successfully!' 
            : 'Medical consultation record saved successfully!';

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
