@extends('layouts.app')

@section('title', 'Prescription & Clinical Record — ' . $consultation->pet->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="prescriptionFormComponent({{ json_encode(old('medication_info', $record->medication_info ?? '')) }})">

    <!-- Top Breadcrumb & Status Bar -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg shadow-sm">
                <i class="fa-solid fa-file-prescription"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-base sm:text-lg font-bold text-slate-800">Veterinary Clinical Record & Prescription (Rx)</h1>
                    <span class="text-[10px] uppercase font-mono px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-semibold hidden sm:inline">#{{ $consultation->consultation_number }}</span>
                </div>
                <p class="text-xs text-slate-500">Document medical diagnosis and issue an official digital prescription for the patient.</p>
            </div>
        </div>

        <div class="flex items-center space-x-2 shrink-0">
            @if($record->exists && !empty($record->medication_info))
                <a href="{{ route('consultation.prescription.show', $consultation) }}" target="_blank"
                   class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold px-3.5 py-2 rounded-xl flex items-center space-x-1.5 transition-colors">
                    <i class="fa-solid fa-print"></i>
                    <span>Preview Rx Slip</span>
                </a>
            @endif
            <a href="{{ route('vet.requests.show', $consultation) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3.5 py-2 rounded-xl flex items-center space-x-1.5 transition-colors">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Request</span>
            </a>
        </div>
    </div>

    <!-- Patient & Doctor Overview Card -->
    <div class="bg-slate-900 text-white rounded-3xl p-6 sm:p-7 shadow-xl border border-slate-800 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-2xl bg-brand-500/20 text-brand-400 border border-brand-500/30 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid {{ $consultation->pet->animalType ? $consultation->pet->animalType->icon : 'fa-paw' }}"></i>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-extrabold tracking-widest text-brand-400 block">Patient Details</span>
                    <h2 class="text-lg font-extrabold text-white leading-tight">{{ $consultation->pet->name }}</h2>
                    <p class="text-xs text-slate-400">{{ $consultation->pet->animalType->name ?? 'Pet' }} • {{ $consultation->pet->breed_name }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="bg-slate-800 border border-slate-700 px-3 py-1.5 rounded-xl text-slate-300">
                    Sex: <strong class="text-white">{{ $consultation->pet->sex }}</strong>
                </span>
                <span class="bg-slate-800 border border-slate-700 px-3 py-1.5 rounded-xl text-slate-300">
                    Age: <strong class="text-white">{{ $consultation->pet->age_text ?: 'N/A' }}</strong>
                </span>
                <span class="bg-slate-800 border border-slate-700 px-3 py-1.5 rounded-xl text-slate-300">
                    Weight: <strong class="text-white">{{ $consultation->pet->weight ?: 'N/A' }}</strong>
                </span>
                <span class="bg-brand-950 border border-brand-800 text-brand-300 px-3 py-1.5 rounded-xl font-semibold">
                    Client: <strong class="text-white">{{ $consultation->client->name }}</strong>
                </span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs text-slate-400">
            <div>
                Reason for visit: <strong class="text-slate-200">"{{ $consultation->reason }}"</strong>
            </div>
            <div class="font-mono text-[11px] text-slate-400">
                Attending Vet: <strong class="text-emerald-400">Dr. {{ Auth::user()->name }}</strong> (PRC Lic: {{ Auth::user()->vetProfile->license_number ?? 'PRC-VET' }})
            </div>
        </div>
    </div>

    <!-- Main Consultation Record & Prescription Form -->
    <div class="bg-white rounded-3xl shadow-xl border border-slate-200/80 p-6 sm:p-8 space-y-6">

        <form method="POST" action="{{ route('vet.records.store', $consultation) }}" class="space-y-6" @submit="compileMedications()">
            @csrf

            <!-- Section 1: Clinical Notes -->
            <div class="space-y-4">
                <div class="flex items-center space-x-2 text-slate-900 border-b border-slate-100 pb-2">
                    <i class="fa-solid fa-clipboard-check text-brand-600 text-sm"></i>
                    <h3 class="font-bold text-sm uppercase tracking-wider text-slate-800">1. Clinical Notes & Observations</h3>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label for="symptoms" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Observed Symptoms & Complaints *
                        </label>
                        <textarea name="symptoms" id="symptoms" rows="3" required
                                  placeholder="Detail physical symptoms observed or reported during the video/chat consultation (e.g. lethargy, coughing, reduced appetite)..."
                                  class="w-full rounded-2xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500 shadow-sm leading-relaxed">{{ old('symptoms', $record->symptoms) }}</textarea>
                    </div>

                    <div>
                        <label for="assessment" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Veterinary Assessment / Clinical Diagnosis *
                        </label>
                        <textarea name="assessment" id="assessment" rows="3" required
                                  placeholder="Provide veterinary diagnosis, provisional evaluation, or clinical assessment..."
                                  class="w-full rounded-2xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500 shadow-sm leading-relaxed">{{ old('assessment', $record->assessment) }}</textarea>
                    </div>

                    <div>
                        <label for="recommendations" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Care Recommendations & Advice
                        </label>
                        <textarea name="recommendations" id="recommendations" rows="3"
                                  placeholder="Dietary changes, rest, hydration, wound management, environmental adjustments..."
                                  class="w-full rounded-2xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500 shadow-sm leading-relaxed">{{ old('recommendations', $record->recommendations) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Section 2: Digital Prescription (Rx) Builder -->
            <div class="space-y-4 pt-4 border-t border-slate-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2">
                    <div class="flex items-center space-x-2 text-emerald-800">
                        <span class="text-2xl font-serif font-black italic tracking-tighter text-emerald-700 leading-none">℞</span>
                        <div>
                            <h3 class="font-bold text-sm uppercase tracking-wider text-slate-900">2. Official Prescription (Rx) & Medications</h3>
                            <p class="text-[11px] text-slate-500">Prescribed medications will appear on the client's official printable Rx slip.</p>
                        </div>
                    </div>

                    <!-- Builder / Freeform Switcher -->
                    <div class="flex items-center space-x-2 self-start sm:self-auto">
                        <button type="button" @click="useBuilder = true"
                                :class="useBuilder ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="text-xs px-3 py-1.5 rounded-lg transition-all flex items-center space-x-1 shadow-sm">
                            <i class="fa-solid fa-list-ol text-[10px]"></i>
                            <span>Medicine Builder</span>
                        </button>
                        <button type="button" @click="useBuilder = false"
                                :class="!useBuilder ? 'bg-slate-800 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="text-xs px-3 py-1.5 rounded-lg transition-all flex items-center space-x-1 shadow-sm">
                            <i class="fa-solid fa-align-left text-[10px]"></i>
                            <span>Freeform Text</span>
                        </button>
                    </div>
                </div>

                <!-- Interactive Medicine Builder Mode -->
                <div x-show="useBuilder" class="space-y-4 bg-emerald-50/40 p-4 sm:p-5 rounded-2xl border border-emerald-200/70" x-transition>
                    
                    <!-- Quick Medication Suggestion Pills -->
                    <div>
                        <span class="text-[11px] font-bold text-emerald-900 block mb-1.5">Common Veterinary Drugs (Click to insert):</span>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="suggestion in suggestions" :key="suggestion.name">
                                <button type="button" @click="addFromSuggestion(suggestion)"
                                        class="bg-white hover:bg-emerald-100 border border-emerald-300 text-emerald-800 text-[11px] font-semibold px-2.5 py-1 rounded-lg transition-all flex items-center space-x-1 shadow-2xs">
                                    <i class="fa-solid fa-plus text-[9px] text-emerald-600"></i>
                                    <span x-text="suggestion.name"></span>
                                    <span class="text-[9px] text-slate-400" x-text="'(' + suggestion.dosage + ')'"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Medicine Items List -->
                    <div class="space-y-3 pt-2">
                        <template x-for="(med, index) in medicines" :key="index">
                            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm space-y-3 relative">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-emerald-800 flex items-center space-x-1.5">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px]" x-text="index + 1"></span>
                                        <span>Prescription Item #<span x-text="index + 1"></span></span>
                                    </span>
                                    <button type="button" @click="removeMedicine(index)" x-show="medicines.length > 1"
                                            class="text-rose-500 hover:text-rose-700 text-xs font-semibold flex items-center space-x-1">
                                        <i class="fa-solid fa-trash-can"></i>
                                        <span>Remove</span>
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                    <div>
                                        <label class="block font-bold text-slate-700 mb-1">Medication / Drug Name *</label>
                                        <input type="text" x-model="med.name" placeholder="e.g. Amoxicillin Clavulanate"
                                               class="w-full rounded-xl border-slate-200 text-xs py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-slate-700 mb-1">Dosage / Strength *</label>
                                        <input type="text" x-model="med.dosage" placeholder="e.g. 250 mg tablet"
                                               class="w-full rounded-xl border-slate-200 text-xs py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-slate-700 mb-1">Quantity to Dispense</label>
                                        <input type="text" x-model="med.quantity" placeholder="e.g. 14 tablets, 1 bottle"
                                               class="w-full rounded-xl border-slate-200 text-xs py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <label class="block font-bold text-slate-700 mb-1">Frequency & Schedule *</label>
                                        <input type="text" x-model="med.frequency" placeholder="e.g. 1 tablet every 12 hours (twice daily)"
                                               class="w-full rounded-xl border-slate-200 text-xs py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-slate-700 mb-1">Duration</label>
                                        <input type="text" x-model="med.duration" placeholder="e.g. 7 consecutive days"
                                               class="w-full rounded-xl border-slate-200 text-xs py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Directions / Sig (Special Instructions)</label>
                                    <input type="text" x-model="med.instructions" placeholder="e.g. Administer orally with a small meal. Do not discontinue early."
                                           class="w-full rounded-xl border-slate-200 text-xs py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Add Another Medicine Button -->
                    <button type="button" @click="addMedicine()"
                            class="bg-white hover:bg-slate-50 border-2 border-dashed border-emerald-300 hover:border-emerald-400 text-emerald-800 font-bold text-xs py-2.5 px-4 rounded-xl w-full flex items-center justify-center space-x-1.5 transition-all shadow-sm">
                        <i class="fa-solid fa-circle-plus text-emerald-600"></i>
                        <span>+ Add Another Medication (Rx)</span>
                    </button>
                </div>

                <!-- Textarea (Always synchronized with builder or edited in Freeform mode) -->
                <div>
                    <label for="medication_info" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Prescription Slip Content Preview / Text
                    </label>
                    <textarea name="medication_info" id="medication_info" rows="4" x-model="rawMedicationInfo"
                              placeholder="Prescription items will appear here and on the printable Rx document..."
                              class="w-full rounded-2xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500 shadow-sm leading-relaxed font-sans"></textarea>
                    <p class="text-[11px] text-slate-500 mt-1">This text is printed on the official veterinary Rx slip. You can review or adjust it directly before saving.</p>
                </div>
            </div>

            <!-- Section 3: Follow-up -->
            <div class="space-y-4 pt-4 border-t border-slate-100">
                <div class="flex items-center space-x-2 text-slate-900 border-b border-slate-100 pb-2">
                    <i class="fa-solid fa-calendar-check text-brand-600 text-sm"></i>
                    <h3 class="font-bold text-sm uppercase tracking-wider text-slate-800">3. Follow-up & Discharge Instructions</h3>
                </div>

                <div>
                    <label for="follow_up_instructions" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Follow-up Directions
                    </label>
                    <input type="text" name="follow_up_instructions" id="follow_up_instructions"
                           value="{{ old('follow_up_instructions', $record->follow_up_instructions) }}"
                           placeholder="e.g. Return for re-evaluation in 5 days, or contact immediately if vomiting occurs"
                           class="w-full rounded-2xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                </div>
            </div>

            <!-- Form Submission Actions -->
            <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                <a href="{{ route('vet.requests.show', $consultation) }}" class="w-full sm:w-auto px-5 py-3 rounded-2xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold text-center transition-colors">
                    Discard Changes
                </a>

                <button type="submit" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold px-8 py-3.5 rounded-2xl shadow-lg shadow-emerald-600/30 transition-all text-sm flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-file-prescription text-base"></i>
                    <span>Save Clinical Record & Issue Prescription</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function prescriptionFormComponent(initialMedication) {
    return {
        useBuilder: true,
        rawMedicationInfo: initialMedication || '',
        suggestions: [
            { name: 'Amoxicillin + Clavulanate', dosage: '250mg Tablet', frequency: 'Twice daily (q12h)', duration: '7 days', quantity: '14 tablets', instructions: 'Administer with food' },
            { name: 'Cephalexin', dosage: '500mg Capsule', frequency: 'Twice daily (q12h)', duration: '10 days', quantity: '20 capsules', instructions: 'Give after meals' },
            { name: 'Metronidazole', dosage: '250mg Tablet', frequency: 'Twice daily (q12h)', duration: '5 days', quantity: '10 tablets', instructions: 'For gastrointestinal treatment' },
            { name: 'Meloxicam Oral Susp.', dosage: '1.5mg/mL Liquid', frequency: 'Once daily (q24h)', duration: '3 days', quantity: '1 bottle (10mL)', instructions: 'Shake well, mix with wet food' },
            { name: 'NexGard Chewable', dosage: 'Afoxolaner 28.3mg', frequency: 'Single chew', duration: 'Monthly', quantity: '1 chewable tablet', instructions: 'Tick & flea prevention' },
            { name: 'Ear Drops (Gentamicin)', dosage: '3 drops per ear', frequency: 'Twice daily', duration: '7 days', quantity: '1 dropper bottle', instructions: 'Clean ear canal prior to application' }
        ],
        medicines: [
            { name: '', dosage: '', frequency: '', duration: '', quantity: '', instructions: '' }
        ],

        init() {
            // If initial raw medication exists and is non-empty, initialize rawMedicationInfo
            if (this.rawMedicationInfo && this.rawMedicationInfo.trim().length > 0) {
                // If it looks like plain text or freeform, keep it in rawMedicationInfo
                this.rawMedicationInfo = this.rawMedicationInfo.trim();
            } else {
                this.medicines = [
                    { name: '', dosage: '', frequency: '', duration: '', quantity: '', instructions: '' }
                ];
            }
        },

        addMedicine() {
            this.medicines.push({ name: '', dosage: '', frequency: '', duration: '', quantity: '', instructions: '' });
        },

        removeMedicine(index) {
            this.medicines.splice(index, 1);
            this.compileMedications();
        },

        addFromSuggestion(s) {
            // If the first medicine item is empty, replace it
            if (this.medicines.length === 1 && !this.medicines[0].name) {
                this.medicines[0] = { ...s };
            } else {
                this.medicines.push({ ...s });
            }
            this.compileMedications();
        },

        compileMedications() {
            if (!this.useBuilder) {
                return; // Vet edited in freeform mode directly
            }

            const validMeds = this.medicines.filter(m => m.name && m.name.trim().length > 0);
            if (validMeds.length === 0) {
                // Keep rawMedicationInfo as whatever it was if builder was left blank
                return;
            }

            let text = '';
            validMeds.forEach((m, idx) => {
                text += `${idx + 1}. ${m.name.trim()}`;
                if (m.dosage) text += ` (${m.dosage.trim()})`;
                if (m.quantity) text += ` — Disp: ${m.quantity.trim()}`;
                text += '\n';

                if (m.frequency || m.duration) {
                    text += `   Sig: ${m.frequency ? m.frequency.trim() : ''}`;
                    if (m.duration) text += ` for ${m.duration.trim()}`;
                    text += '\n';
                }

                if (m.instructions) {
                    text += `   Instructions: ${m.instructions.trim()}\n`;
                }

                if (idx < validMeds.length - 1) text += '\n';
            });

            this.rawMedicationInfo = text.trim();
        }
    };
}
</script>
@endpush
@endsection
