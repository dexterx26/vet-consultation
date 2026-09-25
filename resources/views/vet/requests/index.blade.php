@extends('layouts.app')

@section('title', 'Consultation Requests')

@section('content')
<div class="space-y-6">
    <!-- Header with Title & Stats -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Consultation Requests</h1>
            <p class="text-xs text-slate-500 mt-1">Review patient booking requests, accept appointments, or manage active teleconsultations</p>
        </div>
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200/80 text-slate-700 shadow-sm">
                <i class="fa-solid fa-list-check text-brand-600"></i>
                <span>{{ $consultations->count() }} {{ Str::plural('Request', $consultations->count()) }}</span>
            </span>
        </div>
    </div>

    @php
        $hasActiveFilters = request()->filled('date') || request()->filled('client_name') || request()->filled('pet_type') || (request()->filled('status') && request('status') !== 'all');
    @endphp

    <!-- Filter Bar Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 sm:p-5">
        <form method="GET" action="{{ route('vet.requests.index') }}" class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 text-slate-800 font-bold text-xs uppercase tracking-wider">
                    <i class="fa-solid fa-sliders text-brand-600"></i>
                    <span>Filter Requests</span>
                </div>
                @if($hasActiveFilters)
                    <a href="{{ route('vet.requests.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center space-x-1 transition-colors">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i>
                        <span>Reset Filters</span>
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <!-- Date Filter -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        <i class="fa-regular fa-calendar text-brand-600 mr-1"></i> Consultation Date
                    </label>
                    <input type="date" 
                           name="date" 
                           value="{{ request('date') }}" 
                           class="w-full rounded-xl border-slate-200 text-xs py-2.5 px-3 focus:ring-brand-500 focus:border-brand-500 bg-slate-50/50 hover:bg-white focus:bg-white transition-colors">
                </div>

                <!-- Client Name Filter -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        <i class="fa-regular fa-user text-brand-600 mr-1"></i> Client Name
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                        <input type="text" 
                               name="client_name" 
                               value="{{ request('client_name') }}" 
                               placeholder="Search client name..." 
                               class="w-full rounded-xl border-slate-200 text-xs py-2.5 pl-8 pr-3 focus:ring-brand-500 focus:border-brand-500 bg-slate-50/50 hover:bg-white focus:bg-white transition-colors">
                    </div>
                </div>

                <!-- Pet Type Filter -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        <i class="fa-solid fa-paw text-brand-600 mr-1"></i> Pet Type
                    </label>
                    <select name="pet_type" class="w-full rounded-xl border-slate-200 text-xs py-2.5 px-3 focus:ring-brand-500 focus:border-brand-500 bg-slate-50/50 hover:bg-white focus:bg-white transition-colors">
                        <option value="">All Pet Types</option>
                        @foreach($animalTypes as $type)
                            <option value="{{ $type->id }}" {{ request('pet_type') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter & Actions -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        <i class="fa-solid fa-circle-check text-brand-600 mr-1"></i> Status
                    </label>
                    <div class="flex items-center space-x-2">
                        <select name="status" class="w-full rounded-xl border-slate-200 text-xs py-2.5 px-3 focus:ring-brand-500 focus:border-brand-500 bg-slate-50/50 hover:bg-white focus:bg-white transition-colors">
                            <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="declined" {{ request('status') === 'declined' ? 'selected' : '' }}>Declined</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm hover:shadow transition-all shrink-0 flex items-center space-x-1.5">
                            <i class="fa-solid fa-filter text-[11px]"></i>
                            <span>Filter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Active Filter Badges -->
            @if($hasActiveFilters)
                <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active:</span>

                    @if(request()->filled('date'))
                        <span class="inline-flex items-center space-x-1.5 bg-brand-50 text-brand-700 border border-brand-200 text-xs font-semibold px-2.5 py-1 rounded-lg">
                            <i class="fa-regular fa-calendar text-[10px]"></i>
                            <span>Date: {{ \Carbon\Carbon::parse(request('date'))->format('M d, Y') }}</span>
                            <a href="{{ request()->fullUrlWithQuery(['date' => null]) }}" class="text-brand-400 hover:text-brand-800 ml-0.5">
                                <i class="fa-solid fa-xmark text-[10px]"></i>
                            </a>
                        </span>
                    @endif

                    @if(request()->filled('client_name'))
                        <span class="inline-flex items-center space-x-1.5 bg-brand-50 text-brand-700 border border-brand-200 text-xs font-semibold px-2.5 py-1 rounded-lg">
                            <i class="fa-regular fa-user text-[10px]"></i>
                            <span>Client: "{{ request('client_name') }}"</span>
                            <a href="{{ request()->fullUrlWithQuery(['client_name' => null]) }}" class="text-brand-400 hover:text-brand-800 ml-0.5">
                                <i class="fa-solid fa-xmark text-[10px]"></i>
                            </a>
                        </span>
                    @endif

                    @if(request()->filled('pet_type'))
                        @php
                            $selectedType = $animalTypes->firstWhere('id', request('pet_type'));
                        @endphp
                        <span class="inline-flex items-center space-x-1.5 bg-brand-50 text-brand-700 border border-brand-200 text-xs font-semibold px-2.5 py-1 rounded-lg">
                            <i class="fa-solid fa-paw text-[10px]"></i>
                            <span>Pet: {{ $selectedType ? $selectedType->name : request('pet_type') }}</span>
                            <a href="{{ request()->fullUrlWithQuery(['pet_type' => null]) }}" class="text-brand-400 hover:text-brand-800 ml-0.5">
                                <i class="fa-solid fa-xmark text-[10px]"></i>
                            </a>
                        </span>
                    @endif

                    @if(request()->filled('status') && request('status') !== 'all')
                        <span class="inline-flex items-center space-x-1.5 bg-brand-50 text-brand-700 border border-brand-200 text-xs font-semibold px-2.5 py-1 rounded-lg">
                            <i class="fa-solid fa-circle-check text-[10px]"></i>
                            <span>Status: {{ ucfirst(str_replace('_', ' ', request('status'))) }}</span>
                            <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="text-brand-400 hover:text-brand-800 ml-0.5">
                                <i class="fa-solid fa-xmark text-[10px]"></i>
                            </a>
                        </span>
                    @endif

                    <a href="{{ route('vet.requests.index') }}" class="text-[11px] font-bold text-slate-500 hover:text-slate-800 underline ml-1">
                        Clear all
                    </a>
                </div>
            @endif
        </form>
    </div>

    <!-- Consultations Request List -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse($consultations as $consult)
                <div class="p-6 hover:bg-slate-50/50 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 font-bold flex items-center justify-center text-xl shrink-0 border border-brand-100">
                            <i class="fa-solid {{ $consult->pet && $consult->pet->animalType ? $consult->pet->animalType->icon : 'fa-paw' }}"></i>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                                @if($consult->is_follow_up)
                                    <span class="bg-teal-100 text-teal-800 border border-teal-200 text-[10px] font-extrabold uppercase px-2.5 py-0.5 rounded-full flex items-center space-x-1">
                                        <i class="fa-solid fa-calendar-check text-[9px]"></i>
                                        <span>Follow-up</span>
                                    </span>
                                @endif
                                <span class="text-xs uppercase font-extrabold px-2.5 py-0.5 rounded-full
                                    @if($consult->is_follow_up && $consult->status === 'pending') bg-amber-100 text-amber-800 border border-amber-300
                                    @elseif($consult->status === 'accepted') bg-emerald-100 text-emerald-800
                                    @elseif($consult->status === 'pending') bg-amber-100 text-amber-800
                                    @elseif($consult->status === 'completed') bg-slate-100 text-slate-800
                                    @elseif($consult->status === 'in_progress') bg-blue-100 text-blue-800
                                    @elseif($consult->status === 'reschedule_suggested') bg-purple-100 text-purple-800
                                    @else bg-rose-100 text-rose-800 @endif">
                                    @if($consult->is_follow_up && $consult->status === 'pending')
                                        Awaiting Client Approval
                                    @else
                                        {{ ucfirst(str_replace('_', ' ', $consult->status)) }}
                                    @endif
                                </span>
                                <span class="text-xs text-slate-400 font-mono">#{{ $consult->consultation_number }}</span>
                                <span class="inline-flex items-center space-x-1 text-xs font-semibold text-brand-600 uppercase">
                                    <i class="fa-solid {{ $consult->type === 'video' ? 'fa-video' : 'fa-comments' }} text-[10px]"></i>
                                    <span>{{ $consult->type }}</span>
                                </span>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base mt-1">
                                Client: {{ $consult->client->name }}
                                @if($consult->is_follow_up)
                                    <span class="text-xs font-semibold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-lg border border-teal-100">Follow-up Checkup</span>
                                @endif
                            </h3>
                            <p class="text-xs text-slate-600">
                                Patient(s): <strong class="text-slate-800">{{ $consult->all_pets->pluck('name')->join(', ') }}</strong> • 
                                Scheduled: <strong>{{ $consult->scheduled_at->format('M d, Y @ g:i A') }}</strong> ({{ $consult->duration_minutes ?: 15 }}m) •
                                Fee: <strong>{{ ($consult->credits_cost ?? 0) > 0 ? '₱' . number_format($consult->fee, 2) . ' (' . $consult->credits_cost . ' cr)' : 'Free' }}</strong>
                            </p>
                            @if($consult->suggested_scheduled_at)
                                <p class="text-xs text-purple-700 font-medium mt-0.5">
                                    <i class="fa-solid fa-clock-rotate-left"></i> Proposed New Time: {{ $consult->suggested_scheduled_at->format('M d, Y @ g:i A') }}
                                </p>
                            @endif
                            <p class="text-xs text-slate-500 italic mt-1">"{{ Str::limit($consult->reason, 90) }}"</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 shrink-0">
                        @if($consult->status === 'pending')
                            @if($consult->is_follow_up)
                                <span class="text-xs text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-xl font-medium flex items-center space-x-1">
                                    <i class="fa-solid fa-hourglass-half text-[10px]"></i>
                                    <span>Awaiting Client</span>
                                </span>
                            @else
                                <form method="POST" action="{{ route('vet.requests.accept', $consult) }}">
                                    @csrf
                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-2 rounded-xl shadow-sm transition-colors">
                                        Accept
                                    </button>
                                </form>
                            @endif
                        @endif

                        @if(in_array($consult->status, ['accepted', 'in_progress', 'completed']))
                            @if($consult->type === 'video')
                                <a href="{{ route('consultation.video', $consult) }}" title="Video Call" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs px-3.5 py-2 rounded-xl transition-colors">
                                    <i class="fa-solid fa-video"></i>
                                </a>
                            @endif
                            <a href="{{ route('consultation.chat', $consult) }}" title="Chat Room" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs px-3.5 py-2 rounded-xl transition-colors">
                                <i class="fa-solid fa-comments"></i>
                            </a>
                            <a href="{{ route('vet.records.create', $consult) }}" title="Clinical Notes & Prescription" class="bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 font-semibold text-xs px-3 py-2 rounded-xl transition-colors">
                                Notes
                            </a>
                        @endif

                        <a href="{{ route('vet.requests.show', $consult) }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-2 transition-colors">
                            Details
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    @if($hasActiveFilters)
                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-xl mb-3">
                            <i class="fa-solid fa-filter-circle-xmark"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700">No requests match your filter criteria</h3>
                        <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Try clearing or adjusting your date, client name, or pet type filters to see other consultation requests.</p>
                        <a href="{{ route('vet.requests.index') }}" class="inline-flex items-center space-x-1.5 mt-4 bg-brand-50 text-brand-700 hover:bg-brand-100 font-semibold text-xs px-4 py-2 rounded-xl border border-brand-200 transition-colors">
                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                            <span>Clear All Filters</span>
                        </a>
                    @else
                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-xl mb-3">
                            <i class="fa-solid fa-calendar-xmark"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700">No consultation requests found</h3>
                        <p class="text-xs text-slate-400 mt-1">When clients book appointments with you, they will appear here.</p>
                    @endif
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
