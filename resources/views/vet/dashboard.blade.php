@extends('layouts.app')

@section('title', 'Veterinarian Dashboard')

@section('content')
<div class="space-y-8">
    
    <!-- Pending Verification Notice (If Vet is not yet approved) -->
    @if($isPendingVerification)
        <div class="bg-amber-500 text-white rounded-3xl p-6 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-start space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-white text-2xl shrink-0">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold">Account Verification Pending</h2>
                    <p class="text-xs text-amber-100 mt-0.5">Your professional credentials and license documents are under review by system administrators. You will receive an alert once approved.</p>
                </div>
            </div>
            <span class="bg-white/20 text-white text-xs font-bold px-4 py-2 rounded-xl whitespace-nowrap">Status: Pending Review</span>
        </div>
    @endif

    <!-- Vet Header & Availability Banner -->
    <div class="bg-gradient-to-r from-navy-800 via-slate-900 to-teal-950 rounded-3xl p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="flex items-center space-x-3">
                <span class="text-xs uppercase tracking-widest font-semibold text-brand-400 bg-brand-950/60 px-3 py-1 rounded-full border border-brand-500/30">Licensed Veterinarian</span>
                <span class="text-xs text-slate-400">PRC: {{ $profile->license_number ?? 'PRC-VET' }}</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-2">Welcome, {{ $user->name }}</h1>
            <p class="text-slate-300 text-sm mt-1">{{ $profile->clinic_name ?: 'Teleconsultation Clinic' }} • {{ $profile->city }}, {{ $profile->province }}</p>
        </div>

        <div class="flex items-center space-x-4 bg-white/10 p-4 rounded-2xl border border-white/15 backdrop-blur">
            <div>
                <span class="block text-[10px] uppercase font-bold text-slate-300">Live Status</span>
                <span class="text-sm font-extrabold {{ $profile->is_available ? 'text-emerald-400' : 'text-rose-400' }}">
                    {{ $profile->is_available ? '● Online for Consultations' : '○ Offline' }}
                </span>
            </div>
            <form method="POST" action="{{ route('vet.toggle-availability') }}">
                @csrf
                <button type="submit" class="text-xs font-bold px-4 py-2 rounded-xl transition-all {{ $profile->is_available ? 'bg-rose-500/80 hover:bg-rose-600 text-white' : 'bg-emerald-500 hover:bg-emerald-600 text-white' }}">
                    {{ $profile->is_available ? 'Go Offline' : 'Go Online' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Requests</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-lg"><i class="fa-solid fa-hourglass-half"></i></div>
            </div>
            <span class="text-3xl font-black text-slate-800 mt-2 block">{{ $pendingRequests->count() }}</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Today's Schedule</span>
                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-lg"><i class="fa-solid fa-calendar-day"></i></div>
            </div>
            <span class="text-3xl font-black text-slate-800 mt-2 block">{{ $todayConsultations->count() }}</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Consultation Fee</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg"><i class="fa-solid fa-peso-sign"></i></div>
            </div>
            <span class="text-3xl font-black text-slate-800 mt-2 block">₱{{ number_format($profile->consultation_fee ?? 500, 2) }}</span>
        </div>
    </div>

    <!-- Main Lists Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Pending Consultation Requests -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-800 flex items-center">
                    <i class="fa-solid fa-bell text-amber-500 mr-2"></i> Pending Requests
                </h2>
                <a href="{{ route('vet.requests.index') }}" class="text-xs font-semibold text-brand-600">View All →</a>
            </div>

            @if($pendingRequests->isEmpty())
                <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500 text-xs">
                    No pending booking requests.
                </div>
            @else
                <div class="space-y-3">
                    @foreach($pendingRequests as $req)
                        <div class="bg-white rounded-2xl border border-amber-200 p-5 shadow-sm space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800">{{ $req->client->name }}</span>
                                <span class="bg-amber-100 text-amber-800 font-extrabold px-2 py-0.5 rounded">{{ strtoupper($req->type) }}</span>
                            </div>
                            <p class="text-xs text-slate-600">
                                Pet: <strong class="text-slate-800">{{ $req->pet->name }}</strong> ({{ $req->pet->breed_name }})
                            </p>
                            <p class="text-xs text-slate-500 italic bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                "{{ Str::limit($req->reason, 100) }}"
                            </p>
                            <div class="flex items-center space-x-2 pt-1">
                                <form method="POST" action="{{ route('vet.requests.accept', $req) }}" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs py-2 rounded-xl shadow-sm">
                                        Accept Request
                                    </button>
                                </form>
                                <a href="{{ route('vet.requests.show', $req) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3 py-2 rounded-xl">Review</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Today's Schedule -->
        <div class="space-y-4">
            <h2 class="text-xl font-bold text-slate-800 flex items-center">
                <i class="fa-solid fa-clock text-brand-600 mr-2"></i> Today's Appointments
            </h2>

            @if($todayConsultations->isEmpty())
                <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500 text-xs">
                    No active appointments scheduled for today.
                </div>
            @else
                <div class="space-y-3">
                    @foreach($todayConsultations as $today)
                        <div class="bg-white rounded-2xl border border-brand-200 p-5 shadow-sm flex items-center justify-between">
                            <div>
                                <span class="text-xs font-extrabold text-brand-700">{{ $today->scheduled_at->format('g:i A') }}</span>
                                <h3 class="font-bold text-slate-800 text-sm mt-0.5">{{ $today->client->name }} ({{ $today->pet->name }})</h3>
                                <span class="text-[10px] text-slate-400 capitalize">Type: {{ $today->type }} • Status: {{ $today->status }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($today->type === 'video')
                                    <a href="{{ route('consultation.video', $today) }}" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs px-3 py-2 rounded-xl shadow-sm">
                                        <i class="fa-solid fa-video mr-1"></i> Start Call
                                    </a>
                                @endif
                                <a href="{{ route('consultation.chat', $today) }}" class="bg-slate-800 text-white font-semibold text-xs px-3 py-2 rounded-xl">
                                    <i class="fa-solid fa-comments"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
