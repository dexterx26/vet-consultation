@extends('layouts.app')

@section('title', 'Veterinarian Verifications')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Veterinarian Applications & Management</h1>
            <p class="text-xs text-slate-500 mt-1">Verify submitted credentials, approve applications, or suspend accounts</p>
        </div>
        <div class="flex items-center space-x-2 text-xs">
            <a href="{{ route('admin.vets.index', ['status' => 'all']) }}" class="px-3 py-1.5 rounded-lg border {{ $status === 'all' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-600 border-slate-200' }}">All</a>
            <a href="{{ route('admin.vets.index', ['status' => 'pending']) }}" class="px-3 py-1.5 rounded-lg border {{ $status === 'pending' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-slate-600 border-slate-200' }}">Pending</a>
            <a href="{{ route('admin.vets.index', ['status' => 'active']) }}" class="px-3 py-1.5 rounded-lg border {{ $status === 'active' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-600 border-slate-200' }}">Approved</a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 uppercase font-semibold text-[10px] border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-4">Doctor Name</th>
                        <th class="py-3 px-4">PRC License</th>
                        <th class="py-3 px-4">Clinic / City</th>
                        <th class="py-3 px-4">Exp</th>
                        <th class="py-3 px-4">Fee</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($vets as $v)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3 px-4 font-bold text-slate-800">{{ $v->name }}</td>
                            <td class="py-3 px-4 font-mono text-slate-700">{{ $v->vetProfile->license_number ?? 'N/A' }}</td>
                            <td class="py-3 px-4">{{ $v->vetProfile->clinic_name ?: 'Private Clinic' }} ({{ $v->vetProfile->city ?? 'N/A' }})</td>
                            <td class="py-3 px-4">{{ $v->vetProfile->years_experience ?? 0 }} yrs</td>
                            <td class="py-3 px-4 font-semibold text-slate-800">₱{{ number_format($v->vetProfile->consultation_fee ?? 500, 2) }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase
                                    @if($v->status === 'active') bg-emerald-100 text-emerald-800
                                    @elseif($v->status === 'pending') bg-amber-100 text-amber-800
                                    @else bg-rose-100 text-rose-800 @endif">
                                    {{ $v->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right space-x-2">
                                <a href="{{ route('admin.vets.show', $v) }}" class="bg-brand-600 text-white font-semibold px-3 py-1.5 rounded-lg">Review Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-slate-400">No veterinarian records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
