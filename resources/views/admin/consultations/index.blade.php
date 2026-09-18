@extends('layouts.app')

@section('title', 'Master Consultations List')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">System Consultations Master List</h1>
            <p class="text-xs text-slate-500 mt-1">Audit platform consultations, booking statuses, and clinical records</p>
        </div>
        <div class="flex items-center space-x-2 text-xs">
            <a href="{{ route('admin.consultations.index', ['status' => 'all']) }}" class="px-3 py-1.5 rounded-lg border {{ $status === 'all' ? 'bg-slate-800 text-white' : 'bg-white text-slate-600' }}">All</a>
            <a href="{{ route('admin.consultations.index', ['status' => 'completed']) }}" class="px-3 py-1.5 rounded-lg border {{ $status === 'completed' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600' }}">Completed</a>
            <a href="{{ route('admin.consultations.index', ['status' => 'pending']) }}" class="px-3 py-1.5 rounded-lg border {{ $status === 'pending' ? 'bg-amber-600 text-white' : 'bg-white text-slate-600' }}">Pending</a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 uppercase font-semibold text-[10px] border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-4">Consultation No.</th>
                        <th class="py-3.5 px-4">Client</th>
                        <th class="py-3.5 px-4">Veterinarian</th>
                        <th class="py-3.5 px-4">Pet Patient</th>
                        <th class="py-3.5 px-4">Type</th>
                        <th class="py-3.5 px-4">Fee</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Scheduled Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($consultations as $c)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-800">#{{ $c->consultation_number }}</td>
                            <td class="py-3.5 px-4 font-semibold text-slate-800">{{ $c->client->name }}</td>
                            <td class="py-3.5 px-4 font-semibold text-brand-700">{{ $c->vet->name }}</td>
                            <td class="py-3.5 px-4">{{ $c->pet->name }} ({{ $c->pet->breed_name }})</td>
                            <td class="py-3.5 px-4 font-extrabold uppercase">{{ $c->type }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-800">₱{{ number_format($c->fee, 2) }}</td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase
                                    @if($c->status === 'completed') bg-slate-100 text-slate-800
                                    @elseif($c->status === 'accepted') bg-emerald-100 text-emerald-800
                                    @elseif($c->status === 'pending') bg-amber-100 text-amber-800
                                    @else bg-rose-100 text-rose-800 @endif">
                                    {{ str_replace('_', ' ', $c->status) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500">{{ $c->scheduled_at->format('M d, Y @ g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-slate-400">No consultations recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
