@extends('layouts.app')

@section('title', 'Manage Platform Users')

@section('content')
<div class="space-y-6" x-data="{
    creditModalOpen: false,
    selectedUser: null,
    creditAmount: 300,
    creditNotes: '',
    openAddCredits(user) {
        this.selectedUser = user;
        this.creditAmount = 300;
        this.creditNotes = '';
        this.creditModalOpen = true;
    }
}">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">User Management</h1>
            <p class="text-xs text-slate-500 mt-1">Overview of registered pet owners, credits balance & veterinarians</p>
        </div>
        <div class="flex items-center space-x-2 text-xs">
            <a href="{{ route('admin.users.index', ['role' => 'client']) }}" class="px-3.5 py-2 rounded-xl border font-semibold {{ $role === 'client' ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-slate-600 border-slate-200' }}">Pet Owners (Clients)</a>
            <a href="{{ route('admin.users.index', ['role' => 'veterinarian']) }}" class="px-3.5 py-2 rounded-xl border font-semibold {{ $role === 'veterinarian' ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-slate-600 border-slate-200' }}">Veterinarians</a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 uppercase font-semibold text-[10px] border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-4">User Name</th>
                        <th class="py-3.5 px-4">Email</th>
                        <th class="py-3.5 px-4">Phone</th>
                        <th class="py-3.5 px-4">City / Address</th>
                        @if($role === 'client')
                            <th class="py-3.5 px-4">Credits Balance</th>
                        @endif
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Registered Date</th>
                        @if($role === 'client')
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $u)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-slate-800">
                                <div class="flex items-center space-x-2">
                                    <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <span>{{ $u->name }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">{{ $u->email }}</td>
                            <td class="py-3.5 px-4">{{ $u->phone ?: 'N/A' }}</td>
                            <td class="py-3.5 px-4">{{ $u->clientProfile->city ?? ($u->vetProfile->city ?? 'N/A') }}</td>
                            @if($role === 'client')
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        <i class="fa-solid fa-coins text-amber-500 mr-1.5"></i>
                                        {{ number_format($u->credits ?? 0) }} pts
                                    </span>
                                </td>
                            @endif
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase
                                    @if($u->status === 'active') bg-emerald-100 text-emerald-800
                                    @elseif($u->status === 'pending') bg-amber-100 text-amber-800
                                    @else bg-rose-100 text-rose-800 @endif">
                                    {{ $u->status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-400">{{ $u->created_at->format('M d, Y') }}</td>
                            @if($role === 'client')
                                <td class="py-3.5 px-4 text-right">
                                    <button type="button"
                                            @click="openAddCredits({ id: {{ $u->id }}, name: '{{ addslashes($u->name) }}', credits: {{ $u->credits ?? 0 }} })"
                                            class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-xl bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 text-xs font-semibold transition-all">
                                        <i class="fa-solid fa-plus text-[10px]"></i>
                                        <span>Add Credits</span>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $role === 'client' ? 8 : 6 }}" class="py-6 text-center text-slate-400">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: Add Credits to Client -->
    <div x-show="creditModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.outside="creditModalOpen = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">Top Up Client Credits</h3>
                        <p class="text-xs text-slate-500" x-text="'Client: ' + (selectedUser ? selectedUser.name : '')"></p>
                    </div>
                </div>
                <button @click="creditModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Current Balance Banner -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 flex items-center justify-between text-xs">
                <span class="text-slate-500 font-medium">Current Balance:</span>
                <span class="font-extrabold text-amber-700 font-mono text-sm" x-text="(selectedUser ? selectedUser.credits : 0) + ' Credits'"></span>
            </div>

            <form :action="'/admin/users/' + (selectedUser ? selectedUser.id : '') + '/credits'" method="POST" class="space-y-4">
                @csrf

                <!-- Preset Quick Buttons -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Quick Preset Amounts</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" @click="creditAmount = 300"
                                :class="creditAmount == 300 ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-slate-700 border-slate-200 hover:border-brand-300'"
                                class="py-2 rounded-xl border text-xs font-bold transition-all flex flex-col items-center">
                            <span>+300</span>
                            <span class="text-[9px] font-normal opacity-80">1 Booking</span>
                        </button>
                        <button type="button" @click="creditAmount = 600"
                                :class="creditAmount == 600 ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-slate-700 border-slate-200 hover:border-brand-300'"
                                class="py-2 rounded-xl border text-xs font-bold transition-all flex flex-col items-center">
                            <span>+600</span>
                            <span class="text-[9px] font-normal opacity-80">2 Bookings</span>
                        </button>
                        <button type="button" @click="creditAmount = 1200"
                                :class="creditAmount == 1200 ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-slate-700 border-slate-200 hover:border-brand-300'"
                                class="py-2 rounded-xl border text-xs font-bold transition-all flex flex-col items-center">
                            <span>+1200</span>
                            <span class="text-[9px] font-normal opacity-80">4 Bookings</span>
                        </button>
                    </div>
                </div>

                <!-- Custom Amount Input -->
                <div>
                    <label for="amount" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Amount to Add (Credits) *</label>
                    <div class="relative">
                        <input type="number" name="amount" id="amount" x-model="creditAmount" min="1" max="50000" required
                               class="w-full pl-4 pr-16 py-2.5 rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm font-bold text-slate-800 shadow-sm">
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Credits</span>
                    </div>
                </div>

                <!-- Reference / Note -->
                <div>
                    <label for="notes" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Reference / Payment Note (Optional)</label>
                    <input type="text" name="notes" id="notes" x-model="creditNotes" placeholder="e.g. GCash ref #829103, cash payment, or welcome bonus"
                           class="w-full px-4 py-2.5 rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-xs text-slate-700 shadow-sm">
                </div>

                <!-- Summary Preview -->
                <div class="bg-amber-50/60 border border-amber-200/80 rounded-2xl p-3 text-xs text-amber-900 flex items-center justify-between">
                    <span>New Balance After Top-up:</span>
                    <strong class="font-mono text-sm" x-text="((selectedUser ? selectedUser.credits : 0) + parseInt(creditAmount || 0)) + ' Credits'"></strong>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-2">
                    <button type="button" @click="creditModalOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/30 transition-all flex items-center space-x-1.5">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Confirm Top Up</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
