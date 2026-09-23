@extends('layouts.app')

@section('title', 'Prescription Rx — #' . $consultation->consultation_number . ' — ' . $consultation->pet->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Screen Navigation & Action Bar (Hidden on Print) -->
    <div class="print:hidden bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-200/80 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg shadow-sm">
                <i class="fa-solid fa-file-prescription"></i>
            </div>
            <div>
                <h1 class="text-base font-bold text-slate-800">Veterinary Prescription (Rx)</h1>
                <p class="text-xs text-slate-500">Official digital prescription for {{ $consultation->pet->name }} • Consultation #{{ $consultation->consultation_number }}</p>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            @if(auth()->user()->isVet())
                <a href="{{ route('vet.records.create', $consultation) }}" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center space-x-1.5 transition-colors">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Edit Record</span>
                </a>
                <a href="{{ route('vet.requests.show', $consultation) }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold flex items-center space-x-1.5 transition-colors">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Consultation</span>
                </a>
            @else
                <a href="{{ route('client.bookings.show', $consultation) }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold flex items-center space-x-1.5 transition-colors">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back to Booking</span>
                </a>
            @endif

            <button type="button" onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2 rounded-xl shadow-md shadow-emerald-600/20 flex items-center space-x-2 transition-all">
                <i class="fa-solid fa-print"></i>
                <span>Print / Save PDF</span>
            </button>
        </div>
    </div>

    <!-- Official Printable Prescription Card -->
    <div id="printablePrescription" class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden print:border-0 print:shadow-none print:m-0 print:p-0">
        
        <!-- Header Banner with Clinic & Vet Info -->
        <div class="p-6 sm:p-8 border-b-2 border-slate-900 bg-slate-50/50 print:bg-white print:p-4">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2 text-emerald-800 font-extrabold tracking-wide text-xs uppercase">
                        <i class="fa-solid fa-paw text-emerald-600"></i>
                        <span>Veterinary Teleconsultation & Care</span>
                    </div>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">
                        {{ $consultation->vet->vetProfile->clinic_name ?: 'VET CONSULTATION CLINICAL SERVICES' }}
                    </h2>
                    @if($consultation->vet->vetProfile->clinic_address)
                        <p class="text-xs text-slate-600 font-medium">{{ $consultation->vet->vetProfile->clinic_address }}</p>
                    @endif
                    <p class="text-xs text-slate-500 font-mono">Consultation Ref: <strong>#{{ $consultation->consultation_number }}</strong></p>
                </div>

                <div class="sm:text-right space-y-1 bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/90 shadow-sm print:border print:shadow-none">
                    <h3 class="font-extrabold text-slate-900 text-base">{{ $consultation->vet->name }}</h3>
                    <p class="text-xs text-emerald-700 font-bold">Licensed Veterinarian</p>
                    <div class="text-[11px] text-slate-600 space-y-0.5 font-mono pt-1">
                        <div>PRC License: <strong class="text-slate-900">{{ $consultation->vet->vetProfile->license_number ?? 'PRC-VET' }}</strong></div>
                        @if($consultation->vet->vetProfile->specialization)
                            <div class="font-sans text-[11px] text-slate-500">{{ $consultation->vet->vetProfile->specialization }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Patient & Owner Info Bar -->
        <div class="bg-slate-100/70 border-b border-slate-200 px-6 sm:px-8 py-4 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs print:bg-slate-50 print:px-4 print:py-2">
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Patient (Pet)</span>
                <strong class="text-slate-900 text-sm font-black">{{ $consultation->pet->name }}</strong>
                <span class="text-slate-500 block text-[11px]">{{ $consultation->pet->animalType->name ?? 'Pet' }} • {{ $consultation->pet->breed_name }}</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Owner (Client)</span>
                <strong class="text-slate-900 text-sm">{{ $consultation->client->name }}</strong>
                <span class="text-slate-500 block text-[11px]">{{ $consultation->client->email }}</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Pet Profile</span>
                <div class="text-slate-700 font-medium">
                    <span>Sex: <strong>{{ $consultation->pet->sex }}</strong></span> • 
                    <span>Age: <strong>{{ $consultation->pet->age_text ?: 'N/A' }}</strong></span>
                </div>
                <span class="text-slate-500 block text-[11px]">Weight: {{ $consultation->pet->weight ?: 'Not specified' }}</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Date Prescribed</span>
                <strong class="text-slate-900 text-sm">{{ $record->updated_at ? $record->updated_at->format('F d, Y') : date('F d, Y') }}</strong>
                <span class="text-slate-500 block text-[11px]">{{ $record->updated_at ? $record->updated_at->format('g:i A') : date('g:i A') }}</span>
            </div>
        </div>

        <!-- Main Prescription Body -->
        <div class="p-6 sm:p-8 space-y-6 print:p-4 print:space-y-4 min-h-[460px]">

            <!-- Clinical Assessment / Diagnosis -->
            @if($record->assessment)
                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 text-xs space-y-1 print:bg-white print:border">
                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Clinical Assessment / Diagnosis</span>
                    <p class="text-slate-800 font-semibold leading-relaxed">{{ $record->assessment }}</p>
                </div>
            @endif

            <!-- Rx Symbol & Medications List -->
            <div class="space-y-3">
                <div class="flex items-center space-x-3 text-emerald-800 border-b-2 border-emerald-600/30 pb-2">
                    <span class="text-4xl sm:text-5xl font-serif font-black italic tracking-tighter text-emerald-700 select-none">℞</span>
                    <div>
                        <h4 class="font-extrabold text-sm sm:text-base text-slate-900 uppercase tracking-wider">Prescribed Medication & Dosage</h4>
                        <p class="text-[11px] text-slate-500">Administer exactly as directed by the attending veterinarian</p>
                    </div>
                </div>

                <!-- Structured / Formatted Medication Content -->
                <div class="bg-emerald-50/40 rounded-2xl p-5 sm:p-6 border border-emerald-200/70 space-y-3 print:bg-white print:border print:p-4">
                    <div class="text-slate-900 text-sm font-medium whitespace-pre-line leading-relaxed font-sans">
                        {{ $record->medication_info }}
                    </div>
                </div>
            </div>

            <!-- Recommendations & Instructions -->
            @if($record->recommendations || $record->follow_up_instructions)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs pt-2">
                    @if($record->recommendations)
                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 print:bg-white print:border">
                            <strong class="text-slate-800 text-[11px] uppercase tracking-wider block mb-1 flex items-center">
                                <i class="fa-solid fa-list-check text-emerald-600 mr-1.5"></i> Special Care & Advice
                            </strong>
                            <p class="text-slate-700 leading-relaxed whitespace-pre-line">{{ $record->recommendations }}</p>
                        </div>
                    @endif

                    @if($record->follow_up_instructions)
                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 print:bg-white print:border">
                            <strong class="text-slate-800 text-[11px] uppercase tracking-wider block mb-1 flex items-center">
                                <i class="fa-solid fa-calendar-check text-emerald-600 mr-1.5"></i> Follow-up Instructions
                            </strong>
                            <p class="text-slate-700 leading-relaxed">{{ $record->follow_up_instructions }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Doctor Digital Signature & Verification Block -->
            <div class="pt-8 sm:pt-12 border-t-2 border-slate-200 flex flex-col sm:flex-row items-start sm:items-end justify-between gap-6 print:pt-6">
                <div class="text-[10px] text-slate-400 space-y-1 max-w-sm">
                    <p class="font-bold text-slate-500 uppercase tracking-wider">Telehealth Prescription Disclaimer</p>
                    <p>This digital prescription was legally issued via the Veterinary Teleconsultation Platform following clinical evaluation of the registered patient. Present this document or ref number to an accredited veterinary pharmacy.</p>
                </div>

                <div class="text-center sm:text-right w-full sm:w-auto">
                    <div class="inline-block text-center border-b-2 border-slate-900 pb-1 w-60">
                        <div class="font-serif italic font-bold text-lg text-slate-800 pb-0.5">
                            {{ $consultation->vet->name }}
                        </div>
                    </div>
                    <div class="text-xs font-bold text-slate-800 mt-1">Attending Licensed Veterinarian</div>
                    <div class="text-[11px] text-slate-500 font-mono">PRC Lic. No: {{ $consultation->vet->vetProfile->license_number ?? 'PRC-VET' }}</div>
                    <div class="text-[10px] text-emerald-700 font-bold mt-0.5">
                        <i class="fa-solid fa-shield-check"></i> Digitally Certified & Verified
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Print Stylesheet -->
<style>
@media print {
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
        font-size: 11pt !important;
    }
    nav, footer, .print\:hidden {
        display: none !important;
    }
    #printablePrescription {
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>
@endsection
