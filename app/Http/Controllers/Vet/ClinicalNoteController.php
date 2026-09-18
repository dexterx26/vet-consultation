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

        ConsultationRecord::updateOrCreate(
            ['consultation_id' => $consultation->id],
            $request->only([
                'symptoms', 'assessment', 'recommendations', 'treatment_advice',
                'medication_info', 'follow_up_instructions', 'additional_notes'
            ])
        );

        if ($consultation->status !== 'completed') {
            $consultation->update(['status' => 'completed']);
        }

        AppNotification::create([
            'user_id' => $consultation->client_id,
            'title' => 'Clinical Record Available 📋',
            'message' => 'Dr. ' . Auth::user()->name . ' has added consultation records & prescriptions for ' . $consultation->pet->name . '.',
            'type' => 'info',
            'is_read' => false,
        ]);

        return redirect()->route('vet.requests.show', $consultation)->with('success', 'Medical consultation record saved successfully!');
    }
}
