@extends('layouts.app')

@section('title', 'Platform System Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Platform System Settings</h1>
            <p class="text-xs text-slate-500 mt-1">Configure consultation credit costs and teleconsultation limits</p>
        </div>
        <div class="flex items-center space-x-2">
            <span class="px-3 py-1 bg-brand-50 border border-brand-200 text-brand-700 rounded-full text-xs font-semibold">
                <i class="fa-solid fa-sliders mr-1"></i> Admin Global Controls
            </span>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Card 1: Consultation Credits Cost -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center text-xl mb-4">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">Consultation Credit Cost</h3>
                <p class="text-xs text-slate-500 mt-1 mb-4 leading-relaxed">
                    The number of credits automatically deducted from the client's balance when their booking request is confirmed (accepted).
                </p>

                <div>
                    <label for="booking_credits_cost" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Credits Per Confirmed Booking
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-receipt text-xs"></i>
                        </div>
                        <input type="number" name="booking_credits_cost" id="booking_credits_cost"
                               value="{{ old('booking_credits_cost', $bookingCreditsCost) }}"
                               min="1" max="10000" required
                               class="w-full pl-9 pr-14 py-2.5 rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm font-semibold text-slate-800 shadow-sm">
                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-xs font-bold text-slate-400">
                            credits
                        </div>
                    </div>
                    <span class="text-[11px] text-slate-400 mt-1.5 block">Default is <strong>300 credits</strong>.</span>
                </div>
            </div>

            <!-- Card 2: Video Consultation Time Limit -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-brand-50 border border-brand-200 text-brand-600 flex items-center justify-center text-xl mb-4">
                    <i class="fa-solid fa-stopwatch"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">Video Call Time Limit</h3>
                <p class="text-xs text-slate-500 mt-1 mb-4 leading-relaxed">
                    The maximum allowed duration for real-time video teleconsultation rooms. Once expired, the call ends automatically.
                </p>

                <div>
                    <label for="video_call_time_limit_minutes" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Duration Limit (In Minutes)
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-clock text-xs"></i>
                        </div>
                        <input type="number" name="video_call_time_limit_minutes" id="video_call_time_limit_minutes"
                               value="{{ old('video_call_time_limit_minutes', $videoCallTimeLimit) }}"
                               min="1" max="120" required
                               class="w-full pl-9 pr-14 py-2.5 rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm font-semibold text-slate-800 shadow-sm">
                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-xs font-bold text-slate-400">
                            mins
                        </div>
                    </div>
                    <span class="text-[11px] text-slate-400 mt-1.5 block">Default is <strong>1 min</strong> for quick testing.</span>
                </div>
            </div>

            <!-- Card 3: Default Additional Pet Fee -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center text-xl mb-4">
                    <i class="fa-solid fa-paw"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">Default Extra Pet Fee</h3>
                <p class="text-xs text-slate-500 mt-1 mb-4 leading-relaxed">
                    Default fee added to a consultation when a client books additional pets (unless overridden by the veterinarian).
                </p>

                <div>
                    <label for="default_additional_pet_fee" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Fee Per Extra Pet (₱)
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-xs">
                            ₱
                        </div>
                        <input type="number" step="0.01" name="default_additional_pet_fee" id="default_additional_pet_fee"
                               value="{{ old('default_additional_pet_fee', $defaultAdditionalPetFee) }}"
                               min="0"
                               class="w-full pl-9 pr-4 py-2.5 rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm font-semibold text-slate-800 shadow-sm">
                    </div>
                    <span class="text-[11px] text-slate-400 mt-1.5 block">Default is <strong>₱250.00</strong>.</span>
                </div>
            </div>

            <!-- Card 4: Default Additional Pet Extra Time -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center text-xl mb-4">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">Extra Pet Time Added</h3>
                <p class="text-xs text-slate-500 mt-1 mb-4 leading-relaxed">
                    Extra minutes added to the video call/chat room duration for each additional pet examined during a teleconsultation.
                </p>

                <div>
                    <label for="default_additional_pet_duration" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Duration Added (In Minutes)
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                        </div>
                        <input type="number" name="default_additional_pet_duration" id="default_additional_pet_duration"
                               value="{{ old('default_additional_pet_duration', $defaultAdditionalPetDuration) }}"
                               min="1" max="120"
                               class="w-full pl-9 pr-14 py-2.5 rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm font-semibold text-slate-800 shadow-sm">
                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-xs font-bold text-slate-400">
                            mins
                        </div>
                    </div>
                    <span class="text-[11px] text-slate-400 mt-1.5 block">Default is <strong>15 mins</strong>.</span>
                </div>
            </div>

            <!-- Card 5: Additional Pet Credits Cost -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center text-xl mb-4">
                    <i class="fa-solid fa-circle-plus"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">Extra Pet Credit Cost</h3>
                <p class="text-xs text-slate-500 mt-1 mb-4 leading-relaxed">
                    Number of credits deducted from the client balance for each additional pet included in a booking.
                </p>

                <div>
                    <label for="additional_pet_credits_cost" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Credits Per Extra Pet
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-coins text-xs"></i>
                        </div>
                        <input type="number" name="additional_pet_credits_cost" id="additional_pet_credits_cost"
                               value="{{ old('additional_pet_credits_cost', $additionalPetCreditsCost) }}"
                               min="0" max="10000"
                               class="w-full pl-9 pr-14 py-2.5 rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm font-semibold text-slate-800 shadow-sm">
                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-xs font-bold text-slate-400">
                            credits
                        </div>
                    </div>
                    <span class="text-[11px] text-slate-400 mt-1.5 block">Default is <strong>150 credits</strong>.</span>
                </div>
            </div>

            <!-- Card 6: Consultation Time Extension Packages -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm hover:shadow-md transition-shadow md:col-span-2"
                 x-data="{
                     packages: {{ json_encode($extensionPackages) }},
                     addPackage() {
                         const lastPkg = this.packages[this.packages.length - 1];
                         const nextMin = lastPkg ? parseInt(lastPkg.minutes) + 5 : 10;
                         const nextCredits = lastPkg ? parseInt(lastPkg.credits) + 25 : 50;
                         this.packages.push({ minutes: nextMin, credits: nextCredits });
                     },
                     removePackage(index) {
                         if (this.packages.length > 1) {
                             this.packages.splice(index, 1);
                         } else {
                             alert('At least one extension package must be maintained.');
                         }
                     }
                 }">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-xl bg-teal-50 border border-teal-200 text-teal-600 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Consultation Time Extension Packages</h3>
                            <p class="text-xs text-slate-500">Configure duration tiers and credit costs clients can request during video calls or live chats</p>
                        </div>
                    </div>
                    <button type="button" @click="addPackage()"
                            class="bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl transition-all flex items-center space-x-1.5 shadow-sm shadow-teal-600/20 shrink-0 self-start sm:self-center">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Add Package Tier</span>
                    </button>
                </div>

                <!-- Package Tiers Table / Repeater -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200">
                                <th class="py-3 px-4 rounded-l-xl">Tier</th>
                                <th class="py-3 px-4">Extension Duration</th>
                                <th class="py-3 px-4">Credit Cost</th>
                                <th class="py-3 px-4 rounded-r-xl text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(pkg, index) in packages" :key="index">
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-500" x-text="'#' + (index + 1)"></td>
                                    <td class="py-3 px-4">
                                        <div class="relative max-w-xs">
                                            <input type="number" name="package_minutes[]" x-model.number="pkg.minutes"
                                                   min="1" max="300" required
                                                   class="w-full pr-14 py-2 rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500 text-xs font-bold text-slate-800 shadow-sm">
                                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-[11px] font-bold text-slate-400">
                                                mins
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="relative max-w-xs">
                                            <input type="number" name="package_credits[]" x-model.number="pkg.credits"
                                                   min="0" max="100000" required
                                                   class="w-full pr-16 py-2 rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500 text-xs font-bold text-slate-800 shadow-sm">
                                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-[11px] font-bold text-slate-400">
                                                credits
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <button type="button" @click="removePackage(index)"
                                                class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors inline-flex items-center justify-center text-xs"
                                                title="Remove tier">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 bg-teal-50/60 border border-teal-200/80 rounded-xl p-3 text-[11px] text-teal-900 flex items-start space-x-2">
                    <i class="fa-solid fa-circle-info text-teal-600 text-xs mt-0.5 shrink-0"></i>
                    <span>These packages are dynamically offered to clients when requesting consultation time extensions during video calls or live chats. Veterinarian approval is always required before credits are deducted.</span>
                </div>
            </div>

        </div>

        <!-- Policy Summary Alert -->
        <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 text-xs text-slate-600 flex items-start space-x-3">
            <i class="fa-solid fa-circle-info text-brand-600 text-base mt-0.5"></i>
            <div class="space-y-1">
                <p class="font-bold text-slate-800">Operational Policy Overview</p>
                <p>• <strong>Credits deduction</strong> triggers when Dr. accepts the booking, or when the client agrees to a Dr-suggested new time slot.</p>
                <p>• <strong>Real-time locking</strong> prevents double booking on identical slots even if pending vet acceptance.</p>
                <p>• <strong>Video room countdown</strong> displays real-time remaining minutes/seconds with a 15-second alert before ending.</p>
            </div>
        </div>

        <!-- Action Button -->
        <div class="flex justify-end">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm px-6 py-3 rounded-xl shadow-lg shadow-brand-600/30 transition-all flex items-center space-x-2">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Save Platform Settings</span>
            </button>
        </div>

    </form>
</div>
@endsection
