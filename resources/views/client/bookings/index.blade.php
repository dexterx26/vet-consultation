@extends('layouts.app')

@section('title', 'My Consultations')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">My Teleconsultations</h1>
            <p class="text-xs text-slate-500 mt-1">View your scheduled appointments, consultation history, and medical records</p>
        </div>
        <a href="{{ route('client.vets.search') }}" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs px-4 py-2.5 rounded-xl shadow-md shadow-brand-600/30 transition-all flex items-center space-x-2">
            <i class="fa-solid fa-plus"></i>
            <span>Book New Consultation</span>
        </a>
    </div>

    @if($consultations->isEmpty())
        <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-500">
            <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-calendar-xmark text-3xl"></i>
            </div>
            <h3 class="font-bold text-slate-700 text-lg">No Consultations Yet</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">When you request a consultation with a veterinarian, it will appear here.</p>
            <a href="{{ route('client.vets.search') }}" class="inline-flex items-center space-x-2 bg-brand-600 text-white text-xs font-semibold px-5 py-2.5 rounded-xl mt-6 shadow-md shadow-brand-600/30">
                <i class="fa-solid fa-magnifying-glass"></i> <span>Find a Veterinarian</span>
            </a>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="divide-y divide-slate-100">
                @foreach($consultations as $consult)
                    <div class="p-6 hover:bg-slate-50/50 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-start space-x-4">
                            <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 font-bold flex items-center justify-center text-xl shrink-0 border border-brand-100">
                                <i class="fa-solid {{ $consult->type === 'video' ? 'fa-video' : 'fa-comments' }}"></i>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs uppercase font-extrabold px-2.5 py-0.5 rounded-full
                                        @if($consult->status === 'accepted') bg-emerald-100 text-emerald-800
                                        @elseif($consult->status === 'pending') bg-amber-100 text-amber-800
                                        @elseif($consult->status === 'reschedule_suggested') bg-indigo-100 text-indigo-800 border border-indigo-300 animate-pulse
                                        @elseif($consult->status === 'completed') bg-slate-100 text-slate-800
                                        @else bg-rose-100 text-rose-800 @endif">
                                        {{ $consult->status === 'reschedule_suggested' ? 'New Time Proposed (Action Required)' : ucfirst(str_replace('_', ' ', $consult->status)) }}
                                    </span>
                                    <span class="text-xs text-slate-400 font-mono">#{{ $consult->consultation_number }}</span>
                                    <span class="text-xs font-semibold text-brand-600 uppercase">{{ $consult->type }}</span>
                                </div>
                                <h3 class="font-bold text-slate-800 text-base mt-1">Dr. {{ $consult->vet->name }}</h3>
                                <p class="text-xs text-slate-600">
                                    For: <strong class="text-slate-800">{{ $consult->all_pets->pluck('name')->join(', ') }}</strong> • 
                                    Scheduled: <strong>{{ $consult->scheduled_at->format('M d, Y @ g:i A') }}</strong> • 
                                    Fee: <strong class="text-brand-700">₱{{ number_format($consult->fee, 2) }}</strong> ({{ $consult->duration_minutes ?: 15 }}m)
                                </p>
                                @if($consult->status === 'reschedule_suggested')
                                    <p class="text-xs text-indigo-700 font-semibold mt-1">
                                        <i class="fa-solid fa-bell mr-1"></i> Doctor proposed: {{ $consult->suggested_scheduled_at ? $consult->suggested_scheduled_at->format('M d, Y @ g:i A') : '' }} — Click View Details to confirm or decline.
                                    </p>
                                @endif
                                <p class="text-xs text-slate-500 italic mt-1">"{{ Str::limit($consult->reason, 100) }}"</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2 shrink-0">
                            @if(in_array($consult->status, ['accepted', 'scheduled', 'in_progress', 'completed']))
                                @if($consult->type === 'video')
                                    <a href="{{ route('consultation.video', $consult) }}" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs px-3.5 py-2 rounded-xl shadow-md shadow-brand-600/20">
                                        <i class="fa-solid fa-video mr-1"></i> Video
                                    </a>
                                @endif
                                <a href="{{ route('consultation.chat', $consult) }}" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs px-3.5 py-2 rounded-xl">
                                    <i class="fa-solid fa-comments mr-1"></i> Chat
                                </a>
                            @endif

                            <a href="{{ route('client.bookings.show', $consult) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs px-3.5 py-2 rounded-xl">
                                Details
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
