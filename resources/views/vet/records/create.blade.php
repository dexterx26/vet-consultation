@extends('layouts.app')

@section('title', 'Clinical Consultation Record')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8 space-y-6">
        <div>
            <span class="text-xs uppercase tracking-widest font-semibold text-brand-600">Veterinary Clinical Record</span>
            <h1 class="text-2xl font-bold text-slate-800 mt-1">Patient Record for {{ $consultation->pet->name }}</h1>
            <p class="text-xs text-slate-500">Client: {{ $consultation->client->name }} • Consultation #{{ $consultation->consultation_number }}</p>
        </div>

        <form method="POST" action="{{ route('vet.records.store', $consultation) }}" class="space-y-4">
            @csrf

            <div>
                <label for="symptoms" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Observed Symptoms *</label>
                <textarea name="symptoms" id="symptoms" rows="3" required placeholder="Detail the physical symptoms reported or observed during the teleconsultation..."
                    class="w-full rounded-xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500 shadow-sm">{{ old('symptoms', $record->symptoms) }}</textarea>
            </div>

            <div>
                <label for="assessment" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Veterinary Assessment / Diagnosis *</label>
                <textarea name="assessment" id="assessment" rows="3" required placeholder="Provide clinical diagnosis or assessment summary..."
                    class="w-full rounded-xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500 shadow-sm">{{ old('assessment', $record->assessment) }}</textarea>
            </div>

            <div>
                <label for="recommendations" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Recommendations & Care Instructions</label>
                <textarea name="recommendations" id="recommendations" rows="3" placeholder="Dietary adjustments, wound care, resting advice..."
                    class="w-full rounded-xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500 shadow-sm">{{ old('recommendations', $record->recommendations) }}</textarea>
            </div>

            <div>
                <label for="medication_info" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Prescribed Medication & Dosage</label>
                <textarea name="medication_info" id="medication_info" rows="3" placeholder="e.g. Amoxicillin 250mg - 1 tablet twice daily for 7 days"
                    class="w-full rounded-xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500 shadow-sm">{{ old('medication_info', $record->medication_info) }}</textarea>
            </div>

            <div>
                <label for="follow_up_instructions" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Follow-up Instructions</label>
                <input type="text" name="follow_up_instructions" id="follow_up_instructions" value="{{ old('follow_up_instructions', $record->follow_up_instructions) }}" placeholder="e.g. Follow up in 5 days or if symptoms worsen"
                    class="w-full rounded-xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
            </div>

            <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-semibold py-3.5 rounded-xl shadow-lg shadow-brand-600/30 transition-all text-sm mt-4 flex items-center justify-center space-x-2">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Save Medical Record & Complete Consultation</span>
            </button>
        </form>
    </div>
</div>
@endsection
