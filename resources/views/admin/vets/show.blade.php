@extends('layouts.app')

@section('title', 'Review Veterinarian Application')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="text-xs uppercase font-extrabold px-3 py-1 rounded-full 
                    @if($vet->status === 'active') bg-emerald-100 text-emerald-800
                    @elseif($vet->status === 'pending') bg-amber-100 text-amber-800
                    @else bg-rose-100 text-rose-800 @endif">
                    {{ ucfirst($vet->status) }}
                </span>
                <span class="text-xs font-mono text-slate-400">PRC: {{ $vet->vetProfile->license_number }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-800 mt-2">{{ $vet->name }}</h1>
            <p class="text-xs text-slate-500">{{ $vet->email }} • {{ $vet->phone }}</p>
        </div>

        <div class="flex items-center space-x-2">
            @if($vet->status === 'pending')
                <form method="POST" action="{{ route('admin.vets.approve', $vet) }}">
                    @csrf
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-2.5 rounded-xl shadow-md">
                        <i class="fa-solid fa-check mr-1"></i> Approve Veterinarian
                    </button>
                </form>
            @endif

            @if($vet->status === 'active')
                <form method="POST" action="{{ route('admin.vets.suspend', $vet) }}">
                    @csrf
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2.5 rounded-xl">
                        Suspend Account
                    </button>
                </form>
            @elseif($vet->status === 'suspended')
                <form method="POST" action="{{ route('admin.vets.reactivate', $vet) }}">
                    @csrf
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-2.5 rounded-xl">
                        Reactivate Account
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Uploaded Documents Review -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <h2 class="text-lg font-bold text-slate-800 flex items-center">
            <i class="fa-solid fa-file-pdf text-brand-600 mr-2"></i> Professional Credentials & Documents
        </h2>

        <div class="space-y-3">
            @forelse($vet->vetDocuments as $doc)
                <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                    <div>
                        <span class="font-bold text-slate-800 block">{{ $doc->document_name }}</span>
                        <span class="text-[11px] text-slate-500 capitalize">Type: {{ str_replace('_', ' ', $doc->document_type) }} • Status: {{ $doc->status }}</span>
                    </div>
                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="bg-brand-600 text-white font-semibold px-3 py-1.5 rounded-lg flex items-center space-x-1">
                        <i class="fa-solid fa-eye"></i>
                        <span>View Document</span>
                    </a>
                </div>
            @empty
                <p class="text-xs text-slate-400 italic">No document uploads found.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
