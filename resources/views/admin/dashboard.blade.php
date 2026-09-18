@extends('layouts.app')

@section('title', 'Admin Panel')

@section('content')
<div class="space-y-8">
    
    <!-- Admin Header Banner -->
    <div class="bg-gradient-to-r from-navy-900 via-slate-900 to-indigo-950 rounded-3xl p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <span class="text-xs uppercase tracking-widest font-semibold text-brand-400 bg-brand-950/60 px-3 py-1 rounded-full border border-brand-500/30">System Administration</span>
            <h1 class="text-3xl font-extrabold text-white mt-2">Platform Control Panel</h1>
            <p class="text-slate-300 text-sm mt-1">Manage veterinarian approvals, users, pet categories, and system consultations</p>
        </div>
        <div class="flex items-center space-x-3 shrink-0">
            <a href="{{ route('admin.vets.index') }}" class="bg-amber-500 hover:bg-amber-400 text-slate-9 font-bold px-4 py-3 rounded-xl shadow-lg transition-all text-xs flex items-center space-x-2 text-slate-950">
                <i class="fa-solid fa-user-clock"></i>
                <span>Pending Vet Approvals ({{ $pendingVets }})</span>
            </a>
        </div>
    </div>

    <!-- Admin Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Veterinarians</span>
            <div class="flex items-center justify-between mt-2">
                <span class="text-3xl font-black text-slate-800">{{ $totalVets }}</span>
                <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg">{{ $pendingVets }} Pending</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Registered Clients</span>
            <div class="flex items-center justify-between mt-2">
                <span class="text-3xl font-black text-slate-800">{{ $totalClients }}</span>
                <span class="text-xs font-semibold text-slate-500">Pet Owners</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Consultations</span>
            <div class="flex items-center justify-between mt-2">
                <span class="text-3xl font-black text-slate-800">{{ $totalConsultations }}</span>
                <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">{{ $todayConsultations }} Today</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Active Video Sessions</span>
            <div class="flex items-center justify-between mt-2">
                <span class="text-3xl font-black text-brand-600">{{ $activeCalls }}</span>
                <span class="text-xs font-semibold text-brand-600 bg-brand-50 px-2 py-0.5 rounded">In Progress</span>
            </div>
        </div>
    </div>

    <!-- Recent Vet Applications Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-800 flex items-center">
                <i class="fa-solid fa-user-shield text-brand-600 mr-2"></i> Recent Veterinarian Applications
            </h2>
            <a href="{{ route('admin.vets.index') }}" class="text-xs font-semibold text-brand-600">View All Applications →</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 uppercase font-semibold text-[10px] border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-4">Doctor Name</th>
                        <th class="py-3 px-4">License No.</th>
                        <th class="py-3 px-4">Clinic / City</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentVetApplications as $v)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3 px-4 font-bold text-slate-800">{{ $v->name }}</td>
                            <td class="py-3 px-4 font-mono text-slate-700">{{ $v->vetProfile->license_number ?? 'N/A' }}</td>
                            <td class="py-3 px-4">{{ $v->vetProfile->city ?? 'N/A' }}</td>
                            <td class="py-3 px-4">
                                <span class="bg-amber-100 text-amber-800 font-extrabold px-2 py-0.5 rounded text-[10px] uppercase">
                                    {{ $v->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('admin.vets.show', $v) }}" class="bg-brand-600 text-white font-semibold px-3 py-1.5 rounded-lg">Review Document</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-slate-400">No pending vet applications.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
