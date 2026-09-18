@extends('layouts.app')

@section('title', 'Consultation Requests')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Consultation Requests</h1>
        <p class="text-xs text-slate-500 mt-1">Review patient booking requests, accept appointments, or manage active teleconsultations</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse($consultations as $consult)
                <div class="p-6 hover:bg-slate-50/50 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 font-bold flex items-center justify-center text-xl shrink-0 border border-brand-100">
                            <i class="fa-solid {{ $consult->pet->animalType ? $consult->pet->animalType->icon : 'fa-paw' }}"></i>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs uppercase font-extrabold px-2.5 py-0.5 rounded-full
                                    @if($consult->status === 'accepted') bg-emerald-100 text-emerald-800
                                    @elseif($consult->status === 'pending') bg-amber-100 text-amber-800
                                    @elseif($consult->status === 'completed') bg-slate-100 text-slate-800
                                    @else bg-rose-100 text-rose-800 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $consult->status)) }}
                                </span>
                                <span class="text-xs text-slate-400 font-mono">#{{ $consult->consultation_number }}</span>
                                <span class="text-xs font-semibold text-brand-600 uppercase">{{ $consult->type }}</span>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base mt-1">Client: {{ $consult->client->name }}</h3>
                            <p class="text-xs text-slate-600">
                                Patient: <strong class="text-slate-800">{{ $consult->pet->name }}</strong> ({{ $consult->pet->breed_name }}) • 
                                Scheduled: <strong>{{ $consult->scheduled_at->format('M d, Y @ g:i A') }}</strong>
                            </p>
                            <p class="text-xs text-slate-500 italic mt-1">"{{ Str::limit($consult->reason, 90) }}"</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 shrink-0">
                        @if($consult->status === 'pending')
                            <form method="POST" action="{{ route('vet.requests.accept', $consult) }}">
                                @csrf
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-2 rounded-xl shadow-sm">
                                    Accept
                                </button>
                            </form>
                        @endif

                        @if(in_array($consult->status, ['accepted', 'in_progress', 'completed']))
                            @if($consult->type === 'video')
                                <a href="{{ route('consultation.video', $consult) }}" class="bg-brand-600 text-white font-semibold text-xs px-3.5 py-2 rounded-xl">
                                    <i class="fa-solid fa-video"></i>
                                </a>
                            @endif
                            <a href="{{ route('consultation.chat', $consult) }}" class="bg-slate-800 text-white font-semibold text-xs px-3.5 py-2 rounded-xl">
                                <i class="fa-solid fa-comments"></i>
                            </a>
                            <a href="{{ route('vet.records.create', $consult) }}" class="bg-teal-50 text-teal-800 border border-teal-200 font-semibold text-xs px-3 py-2 rounded-xl">
                                Notes
                            </a>
                        @endif

                        <a href="{{ route('vet.requests.show', $consult) }}" class="text-xs text-slate-500 hover:text-slate-700 font-semibold px-2 py-2">Details</a>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-slate-400 text-xs">No consultation requests found.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
