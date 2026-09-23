@extends('layouts.app')

@section('title', 'Veterinarian Dashboard & Schedule')

@section('content')
<script>
    window.vetCalendarData = @json($calendarConsultations);
    window.vetPendingRequests = @json($pendingRequestsData ?? []);
    window.vetPendingRequestsUrl = "{{ route('vet.dashboard.pending-requests') }}";
    window.vetUserId = {{ auth()->id() }};
</script>

<div class="space-y-8" x-data="vetCalendarComponent()">
    
    <!-- Real-Time Floating Toast Alert when Client Books Consultation -->
    <div x-show="newRequestAlert" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="-translate-y-4 opacity-0 scale-95"
         x-transition:enter-end="translate-y-0 opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-y-0 opacity-100 scale-100"
         x-transition:leave-end="-translate-y-4 opacity-0 scale-95"
         class="fixed top-6 right-6 z-50 max-w-md w-full bg-slate-900/95 backdrop-blur-md text-white rounded-3xl p-5 shadow-2xl border border-amber-500/50 ring-1 ring-amber-500/30"
         x-cloak>
        <div class="flex items-start space-x-3.5">
            <div class="w-11 h-11 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl shrink-0 shadow-lg shadow-amber-500/30 animate-bounce">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-400 bg-amber-950/80 px-2 py-0.5 rounded-full border border-amber-500/40">Real-Time Booking</span>
                    <button @click="newRequestAlert = null" class="text-slate-400 hover:text-white transition-colors text-xs p-1">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <h3 class="text-sm font-extrabold text-white mt-1">New Consultation Request!</h3>
                <p class="text-xs text-slate-300 mt-0.5">
                    <strong class="text-white" x-text="newRequestAlert?.client_name"></strong> requested a 
                    <span class="uppercase font-bold text-amber-400" x-text="newRequestAlert?.type"></span> consultation for 
                    <strong class="text-white" x-text="newRequestAlert?.pet_names"></strong>.
                </p>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center space-x-2">
                    <i class="fa-regular fa-clock text-amber-400"></i>
                    <span x-text="newRequestAlert?.scheduled_at_formatted"></span>
                </div>
                <div class="flex items-center space-x-2 mt-3 pt-2 border-t border-white/10">
                    <form method="POST" :action="newRequestAlert?.accept_url" class="flex-1">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2 px-3 rounded-xl transition-all shadow-sm">
                            Accept Immediately
                        </button>
                    </form>
                    <a :href="newRequestAlert?.show_url" class="bg-white/10 hover:bg-white/20 text-white font-semibold text-xs py-2 px-3 rounded-xl transition-colors">
                        Review
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Real-Time Floating Toast Alert for Availability Status Change -->
    <div x-show="statusToast" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="-translate-y-4 opacity-0 scale-95"
         x-transition:enter-end="translate-y-0 opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-y-0 opacity-100 scale-100"
         x-transition:leave-end="-translate-y-4 opacity-0 scale-95"
         class="fixed top-6 right-6 z-50 max-w-sm w-full bg-slate-900/95 backdrop-blur-md text-white rounded-2xl p-4 shadow-2xl border"
         :class="isAvailable ? 'border-emerald-500/50 ring-1 ring-emerald-500/30' : 'border-rose-500/50 ring-1 ring-rose-500/30'"
         x-cloak>
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-base shrink-0 shadow-md"
                 :class="isAvailable ? 'bg-emerald-500 shadow-emerald-500/30' : 'bg-rose-500 shadow-rose-500/30'">
                <i class="fa-solid" :class="isAvailable ? 'fa-check' : 'fa-power-off'"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="text-xs font-extrabold text-white" x-text="isAvailable ? 'Status: Online' : 'Status: Offline'"></h4>
                <p class="text-[11px] text-slate-300" x-text="statusToastMessage"></p>
            </div>
            <button @click="statusToast = false" class="text-slate-400 hover:text-white p-1 text-xs transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
    
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
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="text-xs uppercase tracking-widest font-semibold text-brand-400 bg-brand-950/60 px-3 py-1 rounded-full border border-brand-500/30">Licensed Veterinarian</span>
                <span class="text-xs text-slate-400">PRC: {{ $profile->license_number ?? 'PRC-VET' }}</span>
                
                <!-- Reverb Live Indicator -->
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold transition-all shadow-sm"
                      :class="isLiveConnected ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-700/50 text-slate-400 border border-slate-600/30'"
                      :title="isLiveConnected ? 'Connected to Laravel Reverb WebSocket' : 'Connecting WebSocket...'">
                    <span class="w-1.5 h-1.5 rounded-full" :class="isLiveConnected ? 'bg-emerald-400 animate-pulse' : 'bg-slate-400'"></span>
                    <span x-text="isLiveConnected ? 'Reverb Live' : 'Connecting...'"></span>
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-2 font-heading">Welcome, {{ $user->name }}</h1>
            <p class="text-slate-300 text-sm mt-1">{{ $profile->clinic_name ?: 'Teleconsultation Clinic' }} • {{ $profile->city }}, {{ $profile->province }}</p>
        </div>

        <div class="flex items-center space-x-4 bg-white/10 p-4 rounded-2xl border border-white/15 backdrop-blur">
            <div>
                <span class="block text-[10px] uppercase font-bold text-slate-300">Live Status</span>
                <span class="text-sm font-extrabold flex items-center gap-1.5 transition-colors duration-200"
                      :class="isAvailable ? 'text-emerald-400' : 'text-rose-400'">
                    <span class="w-2 h-2 rounded-full transition-all" 
                          :class="isAvailable ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400'"></span>
                    <span x-text="isAvailable ? 'Online for Consultations' : 'Offline'">
                        {{ $profile->is_available ? 'Online for Consultations' : 'Offline' }}
                    </span>
                </span>
            </div>
            <form x-ref="availabilityForm" method="POST" action="{{ route('vet.toggle-availability') }}" @submit.prevent="toggleAvailability()">
                @csrf
                <button type="submit" 
                        :disabled="isTogglingAvailability"
                        :class="isAvailable ? 'bg-rose-500/80 hover:bg-rose-600 text-white shadow-rose-900/20' : 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-emerald-900/20'"
                        class="text-xs font-bold px-4 py-2.5 rounded-xl transition-all shadow-md flex items-center space-x-2 disabled:opacity-60 cursor-pointer">
                    <span x-show="isTogglingAvailability" class="inline-flex items-center space-x-1.5" x-cloak>
                        <i class="fa-solid fa-spinner fa-spin text-xs"></i>
                        <span>Updating...</span>
                    </span>
                    <span x-show="!isTogglingAvailability" class="inline-flex items-center space-x-1.5">
                        <i class="fa-solid text-xs" :class="isAvailable ? 'fa-power-off' : 'fa-circle-check'"></i>
                        <span x-text="isAvailable ? 'Go Offline' : 'Go Online'">
                            {{ $profile->is_available ? 'Go Offline' : 'Go Online' }}
                        </span>
                    </span>
                </button>
            </form>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Requests</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-lg"
                     :class="justReceivedRequest ? 'animate-bounce' : ''">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
            </div>
            <span class="text-3xl font-black text-slate-800 mt-2 block transition-all duration-300"
                  :class="justReceivedRequest ? 'text-amber-600 scale-110' : ''"
                  x-text="pendingRequestsCount">
                {{ $pendingRequests->count() }}
            </span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Today's Schedule</span>
                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-lg">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
            </div>
            <span class="text-3xl font-black text-slate-800 mt-2 block">{{ $todayConsultations->count() }}</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Month Total</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
            </div>
            <span class="text-3xl font-black text-slate-800 mt-2 block" x-text="monthConsultationsCount"></span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Consultation Fee</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg">
                    <i class="fa-solid fa-peso-sign"></i>
                </div>
            </div>
            <span class="text-3xl font-black text-slate-800 mt-2 block">₱{{ number_format($profile->consultation_fee ?? 500, 2) }}</span>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- INTERACTIVE VET CALENDAR & SCHEDULE SECTION -->
    <!-- ========================================================================= -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden" id="vet-calendar-section">
        
        <!-- Calendar Header Toolbar -->
        <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-b from-slate-50/60 to-white">
            <div class="flex items-center space-x-3">
                <div class="w-11 h-11 rounded-2xl bg-brand-500 text-white flex items-center justify-center text-lg shadow-sm shadow-brand-500/30 shrink-0">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900 font-heading flex items-center space-x-2">
                        <span>Consultation Calendar</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-brand-50 text-brand-700 border border-brand-200" x-text="filteredConsultations.length + ' Bookings'"></span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Manage patient appointments, scheduled calls, and consultation workflows</p>
                </div>
            </div>

            <!-- Month Navigator & Quick Views -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Month Navigator -->
                <div class="flex items-center bg-white border border-slate-200/90 rounded-2xl p-1 shadow-sm">
                    <button type="button" 
                            @click="prevMonth()" 
                            class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-600 hover:text-brand-600 hover:bg-slate-50 transition-colors"
                            title="Previous Month">
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </button>
                    <span class="px-3 text-xs font-extrabold text-slate-800 min-w-[130px] text-center" x-text="currentMonthName"></span>
                    <button type="button" 
                            @click="nextMonth()" 
                            class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-600 hover:text-brand-600 hover:bg-slate-50 transition-colors"
                            title="Next Month">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </button>
                </div>

                <!-- Today Button -->
                <button type="button" 
                        @click="goToToday()" 
                        class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-2xl transition-colors shadow-sm">
                    Today
                </button>

                <!-- View Switcher (Month / Week / Day) -->
                <div class="flex items-center bg-slate-100 p-1 rounded-2xl">
                    <button type="button" 
                            @click="viewMode = 'month'"
                            :class="viewMode === 'month' ? 'bg-white text-brand-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-3 py-1.5 rounded-xl text-xs transition-all">
                        Month
                    </button>
                    <button type="button" 
                            @click="viewMode = 'week'"
                            :class="viewMode === 'week' ? 'bg-white text-brand-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-3 py-1.5 rounded-xl text-xs transition-all">
                        Week
                    </button>
                    <button type="button" 
                            @click="viewMode = 'day'"
                            :class="viewMode === 'day' ? 'bg-white text-brand-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-3 py-1.5 rounded-xl text-xs transition-all">
                        Day
                    </button>
                </div>

                <!-- Type Filter Dropdown -->
                <select x-model="typeFilter" class="bg-white border-slate-200 text-xs rounded-2xl py-1.5 px-3 font-semibold text-slate-700 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                    <option value="all">All Types</option>
                    <option value="video">Video Call</option>
                    <option value="chat">Chat</option>
                </select>

                <!-- Status Filter Dropdown -->
                <select x-model="statusFilter" class="bg-white border-slate-200 text-xs rounded-2xl py-1.5 px-3 font-semibold text-slate-700 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                    <option value="all">All Statuses</option>
                    <option value="accepted">Accepted / Confirmed</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>

        <!-- Calendar Main Body (Split Grid: Calendar on Left, Selected Day Schedule on Right) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 divide-y lg:divide-y-0 lg:divide-x divide-slate-100">
            
            <!-- LEFT: Interactive Calendar Grid (8 cols on lg) -->
            <div class="lg:col-span-8 p-4 sm:p-6 space-y-4">
                
                <!-- 1. MONTH VIEW -->
                <div x-show="viewMode === 'month'" x-cloak class="space-y-2">
                    <!-- Day of Week Headers -->
                    <div class="grid grid-cols-7 gap-1 text-center mb-1">
                        <template x-for="dayName in dayNames" :key="dayName">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider py-1" x-text="dayName"></span>
                        </template>
                    </div>

                    <!-- Month Days 7x5 or 7x6 Grid -->
                    <div class="grid grid-cols-7 gap-1.5">
                        <template x-for="(day, index) in calendarDays" :key="index">
                            <div @click="selectDate(day.date)"
                                 :class="{
                                     'bg-slate-50/40 text-slate-300': !day.isCurrentMonth,
                                     'bg-white text-slate-800 hover:bg-slate-50/80': day.isCurrentMonth && !day.isSelected,
                                     'ring-2 ring-brand-500 bg-brand-50/40 border-brand-400': day.isSelected,
                                     'border-slate-200/90': !day.isSelected,
                                 }"
                                 class="min-h-[85px] sm:min-h-[100px] p-2 rounded-2xl border flex flex-col justify-between cursor-pointer transition-all duration-150 relative group">
                                
                                <!-- Day Header Number & Today Badge -->
                                <div class="flex items-center justify-between">
                                    <span :class="{
                                        'bg-brand-600 text-white font-extrabold shadow-sm': day.isToday,
                                        'font-bold text-slate-700': day.isCurrentMonth && !day.isToday,
                                        'text-slate-300 font-medium': !day.isCurrentMonth,
                                    }" 
                                    class="w-6 h-6 rounded-full text-xs flex items-center justify-center" 
                                    x-text="day.dayNumber"></span>

                                    <!-- Appointment count chip if any -->
                                    <template x-if="day.consultations.length > 0">
                                        <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded-full bg-brand-100 text-brand-800 flex items-center space-x-0.5">
                                            <span x-text="day.consultations.length"></span>
                                        </span>
                                    </template>
                                </div>

                                <!-- Consultation Events Preview Chips -->
                                <div class="space-y-1 mt-1 overflow-hidden">
                                    <!-- First 2 appointments preview -->
                                    <template x-for="(c, cIdx) in day.consultations.slice(0, 2)" :key="c.id">
                                        <div :class="statusBadgeClass(c.status)" 
                                             class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md truncate flex items-center space-x-1 border">
                                            <i class="fa-solid text-[8px]" :class="c.type === 'video' ? 'fa-video' : 'fa-comments'"></i>
                                            <span class="truncate" x-text="c.time + ' ' + c.pet_names"></span>
                                        </div>
                                    </template>

                                    <!-- More count indicator -->
                                    <template x-if="day.consultations.length > 2">
                                        <div class="text-[9px] font-bold text-slate-500 pl-1">
                                            +<span x-text="day.consultations.length - 2"></span> more
                                        </div>
                                    </template>
                                </div>

                                <!-- Selected Day Dot Indicator -->
                                <template x-if="day.isSelected">
                                    <div class="absolute bottom-1 right-2 w-1.5 h-1.5 rounded-full bg-brand-600"></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 2. WEEK VIEW -->
                <div x-show="viewMode === 'week'" x-cloak class="space-y-3">
                    <div class="flex items-center justify-between px-1">
                        <span class="text-xs font-bold text-slate-600">7-Day Weekly Breakdown</span>
                        <div class="flex items-center space-x-1">
                            <button type="button" @click="prevWeek()" class="p-1 text-slate-500 hover:text-brand-600 text-xs px-2 py-1 bg-slate-100 rounded-lg">
                                <i class="fa-solid fa-chevron-left mr-1"></i> Prev Week
                            </button>
                            <button type="button" @click="nextWeek()" class="p-1 text-slate-500 hover:text-brand-600 text-xs px-2 py-1 bg-slate-100 rounded-lg">
                                Next Week <i class="fa-solid fa-chevron-right ml-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- 7 Columns for the Week -->
                    <div class="grid grid-cols-1 sm:grid-cols-7 gap-2">
                        <template x-for="day in weekDays" :key="day.date">
                            <div @click="selectDate(day.date)"
                                 :class="{
                                     'ring-2 ring-brand-500 bg-brand-50/50 border-brand-400': day.isSelected,
                                     'border-slate-200 bg-white hover:bg-slate-50/70': !day.isSelected,
                                 }"
                                 class="p-2.5 rounded-2xl border min-h-[160px] cursor-pointer flex flex-col transition-all">
                                <div class="text-center pb-2 border-b border-slate-100">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase" x-text="day.dayName"></span>
                                    <span :class="day.isToday ? 'bg-brand-600 text-white font-extrabold w-6 h-6 mx-auto rounded-full flex items-center justify-center mt-0.5' : 'text-sm font-bold text-slate-800'"
                                          x-text="day.dayNumber"></span>
                                </div>

                                <!-- Appointments in this day of week -->
                                <div class="space-y-1.5 mt-2 flex-1 overflow-y-auto max-h-[180px] custom-scrollbar">
                                    <template x-for="c in day.consultations" :key="c.id">
                                        <div :class="statusBadgeClass(c.status)" class="p-1.5 rounded-xl border text-[11px] space-y-0.5">
                                            <div class="font-extrabold flex items-center justify-between">
                                                <span x-text="c.time"></span>
                                                <i class="fa-solid text-[9px]" :class="c.type === 'video' ? 'fa-video' : 'fa-comments'"></i>
                                            </div>
                                            <div class="font-bold truncate text-slate-800" x-text="c.pet_names"></div>
                                            <div class="text-[10px] text-slate-500 truncate" x-text="c.client_name"></div>
                                        </div>
                                    </template>

                                    <template x-if="day.consultations.length === 0">
                                        <div class="text-[10px] text-slate-300 text-center py-4 italic">No visits</div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 3. DAY VIEW -->
                <div x-show="viewMode === 'day'" x-cloak class="space-y-3">
                    <div class="flex items-center justify-between bg-slate-50 p-3 rounded-2xl border border-slate-200">
                        <button type="button" @click="prevDay()" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 rounded-xl hover:bg-slate-100">
                            <i class="fa-solid fa-chevron-left mr-1"></i> Prev Day
                        </button>
                        <span class="text-xs font-extrabold text-slate-800" x-text="selectedDateFormatted"></span>
                        <button type="button" @click="nextDay()" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 rounded-xl hover:bg-slate-100">
                            Next Day <i class="fa-solid fa-chevron-right ml-1"></i>
                        </button>
                    </div>

                    <div class="space-y-2">
                        <template x-if="selectedDateConsultations.length === 0">
                            <div class="p-8 text-center bg-slate-50/60 rounded-2xl border border-dashed border-slate-200 text-slate-400 text-xs">
                                <i class="fa-solid fa-calendar-day text-2xl mb-1 text-slate-300 block"></i>
                                No appointments scheduled for this day.
                            </div>
                        </template>

                        <template x-for="c in selectedDateConsultations" :key="c.id">
                            <div class="p-4 bg-white border border-slate-200 rounded-2xl flex items-center justify-between gap-4 shadow-sm hover:border-brand-300 transition-all">
                                <div class="flex items-start space-x-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg shrink-0">
                                        <i class="fa-solid" :class="c.animal_icon"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs font-extrabold text-brand-700" x-text="c.time"></span>
                                            <span :class="statusBadgeClass(c.status)" class="text-[10px] font-bold px-2 py-0.5 rounded-md uppercase" x-text="c.status"></span>
                                            <span class="text-xs font-semibold text-slate-400 font-mono" x-text="'#' + c.consultation_number"></span>
                                        </div>
                                        <h4 class="font-bold text-slate-800 text-sm mt-0.5">
                                            Client: <span x-text="c.client_name"></span> • Patient: <strong x-text="c.pet_names"></strong>
                                        </h4>
                                        <p class="text-xs text-slate-500 italic mt-0.5" x-text="'&quot;' + c.reason + '&quot;'"></p>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2 shrink-0">
                                    <template x-if="c.type === 'video'">
                                        <a :href="c.video_url" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs px-3.5 py-2 rounded-xl shadow-sm transition-colors">
                                            <i class="fa-solid fa-video mr-1"></i> Call
                                        </a>
                                    </template>
                                    <a :href="c.chat_url" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs px-3.5 py-2 rounded-xl shadow-sm transition-colors">
                                        <i class="fa-solid fa-comments"></i>
                                    </a>
                                    <a :href="c.show_url" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-2">
                                        Details
                                    </a>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Status Legend -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500">
                    <div class="flex items-center space-x-4">
                        <span class="font-bold text-slate-700 uppercase tracking-wider">Legend:</span>
                        <span class="flex items-center space-x-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                            <span>Confirmed / Accepted</span>
                        </span>
                        <span class="flex items-center space-x-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
                            <span>Pending Request</span>
                        </span>
                        <span class="flex items-center space-x-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span>
                            <span>In Progress</span>
                        </span>
                        <span class="flex items-center space-x-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-400 inline-block"></span>
                            <span>Completed</span>
                        </span>
                    </div>

                    <a href="{{ route('vet.requests.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 flex items-center space-x-1">
                        <span>Go to All Requests</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- RIGHT: Selected Day Appointments Drawer / Agenda Panel (4 cols on lg) -->
            <div class="lg:col-span-4 p-5 sm:p-6 bg-slate-50/50 flex flex-col justify-between">
                <div>
                    <!-- Selected Day Header -->
                    <div class="flex items-start justify-between gap-2 pb-4 border-b border-slate-200/80">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Selected Date</span>
                            <h3 class="text-base font-extrabold text-slate-900 font-heading" x-text="selectedDateFormatted"></h3>
                        </div>
                        <span class="px-2.5 py-1 rounded-xl text-xs font-bold shrink-0" 
                              :class="selectedDateConsultations.length > 0 ? 'bg-brand-100 text-brand-800' : 'bg-slate-200/70 text-slate-600'"
                              x-text="selectedDateConsultations.length + ' Booking' + (selectedDateConsultations.length === 1 ? '' : 's')">
                        </span>
                    </div>

                    <!-- Consultation Cards List on Selected Date -->
                    <div class="mt-4 space-y-3 max-h-[460px] overflow-y-auto custom-scrollbar pr-1">
                        <template x-for="consult in selectedDateConsultations" :key="consult.id">
                            <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-sm hover:shadow transition-all space-y-3">
                                
                                <!-- Top: Time & Status -->
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center space-x-1.5 font-extrabold text-slate-800">
                                        <i class="fa-regular fa-clock text-brand-600"></i>
                                        <span x-text="consult.time"></span>
                                        <span class="text-[10px] text-slate-400 font-normal" x-text="'(' + consult.duration + 'm)'"></span>
                                    </div>
                                    <span :class="statusBadgeClass(consult.status)" 
                                          class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full border" 
                                          x-text="consult.status.replace('_', ' ')">
                                    </span>
                                </div>

                                <!-- Middle: Patient & Client info -->
                                <div class="flex items-start space-x-3 pt-1">
                                    <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg shrink-0 border border-brand-100">
                                        <i class="fa-solid" :class="consult.animal_icon"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="font-bold text-slate-900 text-sm truncate" x-text="consult.pet_names"></h4>
                                        <p class="text-xs text-slate-500 truncate">
                                            Client: <strong class="text-slate-700" x-text="consult.client_name"></strong>
                                        </p>
                                        <div class="flex items-center space-x-1 text-[11px] text-brand-600 font-semibold uppercase mt-0.5">
                                            <i class="fa-solid text-[9px]" :class="consult.type === 'video' ? 'fa-video' : 'fa-comments'"></i>
                                            <span x-text="consult.type + ' Consultation'"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reason snippet -->
                                <template x-if="consult.reason">
                                    <p class="text-[11px] text-slate-600 italic bg-slate-50 p-2 rounded-xl border border-slate-100" x-text="'&quot;' + consult.reason + '&quot;'"></p>
                                </template>

                                <!-- Direct Actions -->
                                <div class="pt-2 border-t border-slate-100 flex items-center gap-2">
                                    <!-- Video Call Button -->
                                    <template x-if="consult.type === 'video' && ['accepted', 'in_progress', 'scheduled'].includes(consult.status)">
                                        <a :href="consult.video_url" class="flex-1 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs py-2 px-3 rounded-xl text-center shadow-sm transition-colors flex items-center justify-center space-x-1.5">
                                            <i class="fa-solid fa-video text-[10px]"></i>
                                            <span>Start Call</span>
                                        </a>
                                    </template>

                                    <!-- Chat Button -->
                                    <a :href="consult.chat_url" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs py-2 px-3 rounded-xl shadow-sm transition-colors flex items-center justify-center space-x-1">
                                        <i class="fa-solid fa-comments text-[11px]"></i>
                                        <span>Chat</span>
                                    </a>

                                    <!-- Notes Button (if applicable) -->
                                    <template x-if="['accepted', 'in_progress', 'completed'].includes(consult.status)">
                                        <a :href="consult.notes_url" title="Clinical Notes & Prescription" class="bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 font-semibold text-xs py-2 px-2.5 rounded-xl transition-colors">
                                            <i class="fa-solid fa-file-medical"></i>
                                        </a>
                                    </template>

                                    <!-- Details Link -->
                                    <a :href="consult.show_url" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-2">
                                        Details
                                    </a>
                                </div>

                            </div>
                        </template>

                        <!-- Empty State for Selected Date -->
                        <template x-if="selectedDateConsultations.length === 0">
                            <div class="py-10 text-center space-y-2">
                                <div class="w-12 h-12 rounded-full bg-white text-slate-300 mx-auto flex items-center justify-center text-xl shadow-sm border border-slate-200/60">
                                    <i class="fa-regular fa-calendar-check"></i>
                                </div>
                                <h4 class="text-xs font-bold text-slate-700">No appointments on this date</h4>
                                <p class="text-[11px] text-slate-400 max-w-[200px] mx-auto">Click on highlighted calendar days with badges to inspect upcoming bookings.</p>
                                <button type="button" 
                                        @click="goToToday()" 
                                        class="mt-2 text-xs font-bold text-brand-600 hover:text-brand-700 underline">
                                    Jump to Today
                                </button>

                                <template x-if="nextUpcomingDate && selectedDate !== nextUpcomingDate">
                                    <div class="pt-2">
                                        <button type="button" 
                                                @click="selectDate(nextUpcomingDate)" 
                                                class="inline-flex items-center space-x-1.5 text-xs font-bold text-brand-700 bg-brand-50 hover:bg-brand-100 px-3.5 py-1.5 rounded-xl border border-brand-200 transition-colors shadow-sm">
                                            <i class="fa-solid fa-calendar-day text-brand-600"></i>
                                            <span>View Booking (<span x-text="nextUpcomingDate"></span>) →</span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Footer Summary inside Agenda Drawer -->
                <div class="pt-4 border-t border-slate-200/80 mt-4 flex items-center justify-between text-xs text-slate-500">
                    <span>Month: <strong class="text-slate-800" x-text="monthConsultationsCount + ' Total'"></strong></span>
                    <a href="{{ route('vet.requests.index') }}" class="font-bold text-brand-600 hover:text-brand-700">View Requests →</a>
                </div>
            </div>

        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- LOWER GRID: PENDING REQUESTS & TODAY'S SCHEDULE -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Pending Consultation Requests -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-800 flex items-center font-heading">
                    <i class="fa-solid fa-bell text-amber-500 mr-2" :class="justReceivedRequest ? 'animate-bounce' : ''"></i> 
                    <span>Pending Requests</span>
                    <span class="ml-2 text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800" x-text="pendingRequestsCount"></span>
                </h2>
                <a href="{{ route('vet.requests.index', ['status' => 'pending']) }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">View All →</a>
            </div>

            <div class="space-y-3">
                <!-- Empty State -->
                <template x-if="pendingRequests.length === 0">
                    <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500 text-xs">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-2xl mb-1 block"></i>
                        All caught up! No pending booking requests.
                    </div>
                </template>

                <!-- Reactive Request Cards List -->
                <template x-for="req in pendingRequests" :key="req.id">
                    <div :class="req.is_new ? 'ring-2 ring-amber-400 bg-amber-50/50 border-amber-300' : 'bg-white border-amber-200 hover:border-amber-300'"
                         class="rounded-2xl border p-5 shadow-sm space-y-3 transition-all duration-300">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-slate-800" x-text="req.client_name"></span>
                                <template x-if="req.is_new">
                                    <span class="bg-amber-500 text-white text-[9px] font-extrabold px-1.5 py-0.5 rounded-full uppercase tracking-wider animate-pulse">Live New</span>
                                </template>
                            </div>
                            <span class="bg-amber-100 text-amber-800 font-extrabold px-2 py-0.5 rounded uppercase" x-text="req.type"></span>
                        </div>
                        <p class="text-xs text-slate-600">
                            Patient(s): <strong class="text-slate-800" x-text="req.pet_names"></strong> • 
                            Scheduled: <strong x-text="req.scheduled_at_formatted"></strong>
                        </p>
                        <template x-if="req.reason">
                            <p class="text-xs text-slate-500 italic bg-slate-50 p-2.5 rounded-xl border border-slate-100" x-text="'&quot;' + req.reason + '&quot;'"></p>
                        </template>
                        <div class="flex items-center space-x-2 pt-1">
                            <form method="POST" :action="req.accept_url" class="flex-1">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs py-2 rounded-xl shadow-sm transition-colors">
                                    Accept Request
                                </button>
                            </form>
                            <button type="button" 
                                    @click="openDeclineModal(req)" 
                                    class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold px-3 py-2 rounded-xl transition-colors">
                                Decline
                            </button>
                            <a :href="req.show_url" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3 py-2 rounded-xl transition-colors">Review</a>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Today's Schedule -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-800 flex items-center font-heading">
                    <i class="fa-solid fa-clock text-brand-600 mr-2"></i> Today's Appointments
                </h2>
                <span class="text-xs font-bold text-slate-500">{{ now()->format('l, M d') }}</span>
            </div>

            @if($todayConsultations->isEmpty())
                <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500 text-xs">
                    <i class="fa-regular fa-calendar-check text-slate-300 text-2xl mb-1 block"></i>
                    No active appointments scheduled for today.
                </div>
            @else
                <div class="space-y-3">
                    @foreach($todayConsultations as $today)
                        <div class="bg-white rounded-2xl border border-brand-200 p-5 shadow-sm flex items-center justify-between hover:border-brand-300 transition-colors">
                            <div>
                                <span class="text-xs font-extrabold text-brand-700">{{ $today->scheduled_at->format('g:i A') }}</span>
                                <h3 class="font-bold text-slate-800 text-sm mt-0.5">{{ $today->client->name }} ({{ $today->all_pets->pluck('name')->join(', ') ?: $today->pet->name }})</h3>
                                <span class="text-[10px] text-slate-400 capitalize">Type: {{ $today->type }} • Status: {{ $today->status }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($today->type === 'video')
                                    <a href="{{ route('consultation.video', $today) }}" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs px-3 py-2 rounded-xl shadow-sm transition-colors">
                                        <i class="fa-solid fa-video mr-1"></i> Start Call
                                    </a>
                                @endif
                                <a href="{{ route('consultation.chat', $today) }}" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs px-3 py-2 rounded-xl transition-colors">
                                    <i class="fa-solid fa-comments"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    <!-- Decline Consultation Modal -->
    <div x-show="declineModalOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         x-cloak>
        <div @click.away="closeDeclineModal()" 
             class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-calendar-xmark"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Decline Consultation</h3>
                        <p class="text-xs text-slate-500">
                            #<span x-text="decliningRequest?.consultation_number"></span> • 
                            <span x-text="decliningRequest?.client_name"></span>
                        </p>
                    </div>
                </div>
                <button type="button" @click="closeDeclineModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <p class="text-xs text-slate-600">
                Please provide a reason for declining. This consultation will be removed from your pending requests and completely hidden from your schedule calendar.
            </p>

            <!-- Quick Suggestions -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Quick Reasons</label>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" 
                            @click="declineReason = 'Schedule conflict with another clinic appointment.'" 
                            class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-2.5 py-1 rounded-xl transition-colors">
                        Schedule Conflict
                    </button>
                    <button type="button" 
                            @click="declineReason = 'Unavailable during requested time slot.'" 
                            class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-2.5 py-1 rounded-xl transition-colors">
                        Unavailable at Time
                    </button>
                    <button type="button" 
                            @click="declineReason = 'Emergency case requiring in-person hospital evaluation.'" 
                            class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-2.5 py-1 rounded-xl transition-colors">
                        Requires In-Person
                    </button>
                </div>
            </div>

            <form @submit.prevent="submitDecline()" :action="decliningRequest?.decline_url" method="POST" class="space-y-4">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Reason for Declining <span class="text-rose-500">*</span></label>
                    <textarea x-model="declineReason" 
                              name="decline_reason" 
                              rows="3" 
                              required 
                              placeholder="Explain why you cannot take this appointment..." 
                              class="w-full text-xs rounded-2xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 p-3"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" 
                            @click="closeDeclineModal()" 
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="isSubmittingDecline || !declineReason.trim()"
                            class="px-4 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white rounded-xl shadow-sm transition-all flex items-center space-x-1.5">
                        <span x-show="!isSubmittingDecline"><i class="fa-solid fa-ban mr-1"></i> Confirm Decline</span>
                        <span x-show="isSubmittingDecline"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Declining...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Alpine.js Calendar Component Script -->
<script>
function vetCalendarComponent(providedData) {
    const allConsultations = (providedData !== undefined && providedData !== null)
        ? providedData
        : (window.vetCalendarData || []);

    return {
        consultations: allConsultations,
        pendingRequests: window.vetPendingRequests || [],
        pendingRequestsCount: (window.vetPendingRequests || []).length,
        pendingRequestsUrl: window.vetPendingRequestsUrl || '{{ route("vet.dashboard.pending-requests") }}',
        vetId: window.vetUserId || null,
        isLiveConnected: false,
        newRequestAlert: null,
        justReceivedRequest: false,
        alertTimeout: null,
        pollingTimer: null,
        declineModalOpen: false,
        decliningRequest: null,
        declineReason: '',
        isSubmittingDecline: false,
        isAvailable: {{ ($profile && $profile->is_available) ? 'true' : 'false' }},
        isTogglingAvailability: false,
        statusToast: false,
        statusToastMessage: '',
        statusToastTimeout: null,

        currentYear: new Date().getFullYear(),
        currentMonth: new Date().getMonth(), // 0 to 11
        selectedDate: '',
        viewMode: 'month', // 'month', 'week', 'day'
        statusFilter: 'all',
        typeFilter: 'all',

        monthNames: [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ],

        dayNames: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],

        getLocalDateStr(dateObj = new Date()) {
            const y = dateObj.getFullYear();
            const m = String(dateObj.getMonth() + 1).padStart(2, '0');
            const d = String(dateObj.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        },

        init() {
            const todayStr = this.getLocalDateStr();
            this.selectedDate = todayStr;
            const now = new Date();
            this.currentYear = now.getFullYear();
            this.currentMonth = now.getMonth();

            // Auto-navigate to month with appointments if current month has none
            if (this.consultations.length > 0) {
                const pad = (n) => String(n).padStart(2, '0');
                const monthPrefix = `${this.currentYear}-${pad(this.currentMonth + 1)}`;
                const hasInCurrentMonth = this.consultations.some(c => c.date && c.date.startsWith(monthPrefix));
                
                if (!hasInCurrentMonth) {
                    const sorted = [...this.consultations].sort((a, b) => (a.date > b.date ? 1 : -1));
                    const next = sorted.find(c => c.date >= todayStr) || sorted[sorted.length - 1];
                    if (next && next.date) {
                        const [y, m, d] = next.date.split('-').map(Number);
                        this.currentYear = y;
                        this.currentMonth = m - 1;
                        this.selectedDate = next.date;
                    }
                }
            }

            // Connect to Laravel Reverb WebSockets for real-time consultation requests (scalable for 10,000+ users with zero polling)
            this.setupEcho();

            // Re-sync only when user switches back to this tab (prevents missing events while tab was suspended)
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.fetchPendingRequests();
                }
            });

            // Re-sync when network connectivity returns
            window.addEventListener('online', () => {
                this.fetchPendingRequests();
            });
        },

        setupEcho() {
            if (!this.vetId) return;

            if (!window.Echo) {
                // Retry after 350ms until Echo is initialized from Vite bundle
                setTimeout(() => this.setupEcho(), 350);
                return;
            }

            try {
                const channel = window.Echo.private(`vet.${this.vetId}`);

                channel.subscribed(() => {
                    this.isLiveConnected = true;
                });

                channel.error(() => {
                    this.isLiveConnected = false;
                });

                // Auto-sync once on socket reconnect if connection was interrupted
                if (window.Echo.connector && window.Echo.connector.pusher && window.Echo.connector.pusher.connection) {
                    window.Echo.connector.pusher.connection.bind('connected', () => {
                        this.isLiveConnected = true;
                        this.fetchPendingRequests();
                    });
                    window.Echo.connector.pusher.connection.bind('disconnected', () => {
                        this.isLiveConnected = false;
                    });
                }

                // 1. Listen for new incoming consultation requests
                channel.listen('.consultation.requested', (event) => {
                    this.handleNewConsultationRequest(event);
                })
                .listen('consultation.requested', (event) => {
                    this.handleNewConsultationRequest(event);
                })
                .listen('.NewConsultationRequest', (event) => {
                    this.handleNewConsultationRequest(event);
                })
                .listen('NewConsultationRequest', (event) => {
                    this.handleNewConsultationRequest(event);
                });

                // 2. Listen for cancelled consultation requests (client cancelled before accept)
                channel.listen('.consultation.cancelled', (event) => {
                    this.handleConsultationCancelled(event);
                })
                .listen('consultation.cancelled', (event) => {
                    this.handleConsultationCancelled(event);
                })
                .listen('.ConsultationCancelled', (event) => {
                    this.handleConsultationCancelled(event);
                })
                .listen('ConsultationCancelled', (event) => {
                    this.handleConsultationCancelled(event);
                });

                // 3. Listen for declined consultations
                channel.listen('.consultation.declined', (event) => {
                    this.handleConsultationCancelled(event);
                })
                .listen('consultation.declined', (event) => {
                    this.handleConsultationCancelled(event);
                });
            } catch (err) {
                console.warn('Laravel Reverb WebSocket subscription error:', err);
            }
        },

        handleConsultationCancelled(event) {
            if (!event || (!event.id && !event.consultation_id)) return;
            const targetId = event.id || event.consultation_id;

            // Remove from pending requests
            this.pendingRequests = this.pendingRequests.filter(r => r.id !== targetId);
            this.pendingRequestsCount = this.pendingRequests.length;

            // Remove from calendar consultations
            this.consultations = this.consultations.filter(c => c.id !== targetId);

            if (event.status === 'cancelled_by_client') {
                this.statusToastMessage = `Client cancelled consultation request #${event.consultation_number || targetId}`;
                this.statusToast = true;
                if (this.statusToastTimeout) clearTimeout(this.statusToastTimeout);
                this.statusToastTimeout = setTimeout(() => { this.statusToast = false; }, 4000);
            }
        },

        handleNewConsultationRequest(event) {
            if (!event || !event.id) return;

            // 1. Add to pending requests list if not already present
            const exists = this.pendingRequests.some(r => r.id === event.id);
            if (!exists) {
                this.pendingRequests.unshift({
                    id: event.id,
                    consultation_number: event.consultation_number,
                    client_name: event.client_name,
                    pet_names: event.pet_names,
                    animal_icon: event.animal_icon || 'fa-paw',
                    type: event.type,
                    scheduled_at_formatted: event.scheduled_at_formatted,
                    reason: event.reason,
                    accept_url: event.accept_url,
                    decline_url: event.decline_url,
                    show_url: event.show_url,
                    is_new: true,
                });
                this.pendingRequestsCount = this.pendingRequests.length;
            }

            // 2. Add to calendar consultations so calendar grid & agenda drawer update dynamically
            const calExists = this.consultations.some(c => c.id === event.id);
            if (!calExists) {
                this.consultations.push({
                    id: event.id,
                    consultation_number: event.consultation_number,
                    date: event.date,
                    time: event.time,
                    time_24: event.time_24 || '',
                    client_name: event.client_name,
                    pet_names: event.pet_names,
                    animal_icon: event.animal_icon || 'fa-paw',
                    type: event.type,
                    status: 'pending',
                    duration: event.duration_minutes || 15,
                    reason: event.reason,
                    video_url: event.video_url,
                    chat_url: event.chat_url,
                    show_url: event.show_url,
                    notes_url: event.notes_url,
                });
            }

            // 3. Highlight Pending Requests stat counter with animation
            this.justReceivedRequest = true;
            setTimeout(() => {
                this.justReceivedRequest = false;
            }, 3500);

            // 4. Play notification sound chime
            this.playNotificationChime();

            // 5. Trigger floating toast notification
            this.newRequestAlert = event;
            if (this.alertTimeout) clearTimeout(this.alertTimeout);
            this.alertTimeout = setTimeout(() => {
                this.newRequestAlert = null;
            }, 10000);
        },

        async fetchPendingRequests() {
            if (!this.pendingRequestsUrl) return;

            try {
                const response = await fetch(this.pendingRequestsUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) return;

                const data = await response.json();
                const fetchedList = data.pending_requests || [];

                // 1. Detect and add newly requested consultations
                let hasBrandNew = false;
                for (const item of fetchedList) {
                    const existing = this.pendingRequests.find(r => r.id === item.id);
                    if (!existing) {
                        item.is_new = true;
                        this.pendingRequests.unshift(item);
                        hasBrandNew = true;

                        // Add to calendar consultations if not present
                        if (!this.consultations.some(c => c.id === item.id)) {
                            this.consultations.push({
                                id: item.id,
                                consultation_number: item.consultation_number,
                                date: item.date,
                                time: item.time,
                                time_24: item.time_24 || '',
                                client_name: item.client_name,
                                pet_names: item.pet_names,
                                animal_icon: item.animal_icon || 'fa-paw',
                                type: item.type,
                                status: 'pending',
                                duration: item.duration_minutes || 15,
                                reason: item.reason,
                                video_url: item.video_url,
                                chat_url: item.chat_url,
                                show_url: item.show_url,
                                notes_url: item.notes_url,
                            });
                        }
                    }
                }

                // 2. Remove items that were accepted or declined elsewhere
                const fetchedIds = new Set(fetchedList.map(r => r.id));
                const removedRequests = this.pendingRequests.filter(r => !fetchedIds.has(r.id));
                if (removedRequests.length > 0) {
                    this.pendingRequests = this.pendingRequests.filter(r => fetchedIds.has(r.id));
                    for (const rem of removedRequests) {
                        // If it was removed from pending because it was declined, remove from calendar too
                        this.consultations = this.consultations.filter(c => c.id !== rem.id);
                    }
                }

                this.pendingRequestsCount = this.pendingRequests.length;

                if (hasBrandNew) {
                    this.justReceivedRequest = true;
                    setTimeout(() => { this.justReceivedRequest = false; }, 3500);
                    this.playNotificationChime();
                }
            } catch (err) {
                // Background poll fails silently
            }
        },

        openDeclineModal(req) {
            this.decliningRequest = req;
            this.declineReason = '';
            this.declineModalOpen = true;
        },

        closeDeclineModal() {
            this.declineModalOpen = false;
            this.decliningRequest = null;
            this.declineReason = '';
            this.isSubmittingDecline = false;
        },

        async submitDecline() {
            if (!this.decliningRequest || !this.declineReason.trim() || this.isSubmittingDecline) return;

            const req = this.decliningRequest;
            const declineUrl = req.decline_url;
            this.isSubmittingDecline = true;

            try {
                const response = await fetch(declineUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        decline_reason: this.declineReason
                    })
                });

                if (response.ok) {
                    // Remove from pending requests
                    this.pendingRequests = this.pendingRequests.filter(r => r.id !== req.id);
                    this.pendingRequestsCount = this.pendingRequests.length;

                    // Immediately remove from calendar consultations so it is hidden from the calendar
                    this.consultations = this.consultations.filter(c => c.id !== req.id);

                    this.closeDeclineModal();
                } else {
                    this.fallbackSubmitDecline(declineUrl, this.declineReason);
                }
            } catch (err) {
                this.fallbackSubmitDecline(declineUrl, this.declineReason);
            } finally {
                this.isSubmittingDecline = false;
            }
        },

        fallbackSubmitDecline(url, reason) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            const r = document.createElement('input');
            r.type = 'hidden';
            r.name = 'decline_reason';
            r.value = reason;
            form.appendChild(r);

            document.body.appendChild(form);
            form.submit();
        },

        showStatusToast(msg) {
            this.statusToastMessage = msg;
            this.statusToast = true;
            if (this.statusToastTimeout) clearTimeout(this.statusToastTimeout);
            this.statusToastTimeout = setTimeout(() => {
                this.statusToast = false;
            }, 3500);
        },

        async toggleAvailability() {
            if (this.isTogglingAvailability) return;
            this.isTogglingAvailability = true;

            try {
                const response = await fetch('{{ route("vet.toggle-availability") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.isAvailable = !!data.is_available;
                    this.showStatusToast(data.message || (this.isAvailable ? 'You are now online for consultations.' : 'You are now offline.'));
                } else {
                    if (this.$refs.availabilityForm) {
                        this.$refs.availabilityForm.submit();
                    }
                }
            } catch (err) {
                if (this.$refs.availabilityForm) {
                    this.$refs.availabilityForm.submit();
                }
            } finally {
                this.isTogglingAvailability = false;
            }
        },

        playNotificationChime() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const now = ctx.currentTime;

                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.type = 'sine';
                // Ascending harmonic two-tone chime
                osc.frequency.setValueAtTime(523.25, now); // C5
                osc.frequency.setValueAtTime(659.25, now + 0.12); // E5
                osc.frequency.setValueAtTime(783.99, now + 0.24); // G5

                gain.gain.setValueAtTime(0.2, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.8);

                osc.start(now);
                osc.stop(now + 0.8);
            } catch (e) {
                // Ignore if audio context autoplay policy blocks before first interaction
            }
        },

        get currentMonthName() {
            return this.monthNames[this.currentMonth] + ' ' + this.currentYear;
        },

        get filteredConsultations() {
            return this.consultations.filter(c => {
                // If doctor declines the consultation, hide it from calendar
                if (c.status === 'declined' || c.status === 'cancelled_by_client' || c.status === 'cancelled_by_vet') {
                    return false;
                }
                if (this.statusFilter !== 'all' && c.status !== this.statusFilter) return false;
                if (this.typeFilter !== 'all' && c.type !== this.typeFilter) return false;
                return true;
            });
        },

        get monthConsultationsCount() {
            const pad = (n) => String(n).padStart(2, '0');
            const monthPrefix = `${this.currentYear}-${pad(this.currentMonth + 1)}`;
            return this.filteredConsultations.filter(c => c.date && c.date.startsWith(monthPrefix)).length;
        },

        get selectedDateFormatted() {
            if (!this.selectedDate) return 'No Date Selected';
            const [y, m, d] = this.selectedDate.split('-').map(Number);
            const dateObj = new Date(y, m - 1, d);
            return dateObj.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        },

        get selectedDateConsultations() {
            return this.filteredConsultations.filter(c => c.date === this.selectedDate);
        },

        get nextUpcomingDate() {
            const today = this.getLocalDateStr();
            const future = this.filteredConsultations
                .filter(c => c.date && c.date >= today)
                .sort((a, b) => (a.date > b.date ? 1 : -1));
            if (future.length > 0) return future[0].date;
            
            const past = this.filteredConsultations
                .filter(c => c.date)
                .sort((a, b) => (a.date > b.date ? 1 : -1));
            return past.length > 0 ? past[past.length - 1].date : null;
        },

        prevMonth() {
            if (this.currentMonth === 0) {
                this.currentMonth = 11;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }
        },

        nextMonth() {
            if (this.currentMonth === 11) {
                this.currentMonth = 0;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }
        },

        goToToday() {
            const now = new Date();
            this.currentYear = now.getFullYear();
            this.currentMonth = now.getMonth();
            this.selectedDate = this.getLocalDateStr(now);
        },

        selectDate(dateStr) {
            this.selectedDate = dateStr;
            const [y, m] = dateStr.split('-').map(Number);
            this.currentYear = y;
            this.currentMonth = m - 1;
        },

        // Month Grid Days computation
        get calendarDays() {
            const days = [];
            const year = this.currentYear;
            const month = this.currentMonth;
            const pad = (n) => String(n).padStart(2, '0');
            const todayStr = this.getLocalDateStr();

            const firstDayIndex = new Date(year, month, 1).getDay();
            const totalDaysInMonth = new Date(year, month + 1, 0).getDate();
            const prevMonthDays = new Date(year, month, 0).getDate();

            // Previous month padding days
            for (let i = firstDayIndex - 1; i >= 0; i--) {
                const dayNum = prevMonthDays - i;
                const prevM = month === 0 ? 11 : month - 1;
                const prevY = month === 0 ? year - 1 : year;
                const dateStr = `${prevY}-${pad(prevM + 1)}-${pad(dayNum)}`;
                const dayConsults = this.filteredConsultations.filter(c => c.date === dateStr);

                days.push({
                    dayNumber: dayNum,
                    date: dateStr,
                    isCurrentMonth: false,
                    isToday: dateStr === todayStr,
                    isSelected: dateStr === this.selectedDate,
                    consultations: dayConsults,
                });
            }

            // Current month days
            for (let d = 1; d <= totalDaysInMonth; d++) {
                const dateStr = `${year}-${pad(month + 1)}-${pad(d)}`;
                const dayConsults = this.filteredConsultations.filter(c => c.date === dateStr);

                days.push({
                    dayNumber: d,
                    date: dateStr,
                    isCurrentMonth: true,
                    isToday: dateStr === todayStr,
                    isSelected: dateStr === this.selectedDate,
                    consultations: dayConsults,
                });
            }

            // Next month padding days to complete grid
            const remaining = (7 - (days.length % 7)) % 7;
            const totalCellsNeeded = days.length + remaining < 35 ? 35 : (days.length + remaining);
            const extraDays = totalCellsNeeded - days.length;

            for (let nextD = 1; nextD <= extraDays; nextD++) {
                const nextM = month === 11 ? 0 : month + 1;
                const nextY = month === 11 ? year + 1 : year;
                const dateStr = `${nextY}-${pad(nextM + 1)}-${pad(nextD)}`;
                const dayConsults = this.filteredConsultations.filter(c => c.date === dateStr);

                days.push({
                    dayNumber: nextD,
                    date: dateStr,
                    isCurrentMonth: false,
                    isToday: dateStr === todayStr,
                    isSelected: dateStr === this.selectedDate,
                    consultations: dayConsults,
                });
            }

            return days;
        },

        // Week Days computation
        get weekDays() {
            const days = [];
            const pad = (n) => String(n).padStart(2, '0');
            const todayStr = this.getLocalDateStr();
            
            const [y, m, d] = (this.selectedDate || todayStr).split('-').map(Number);
            const current = new Date(y, m - 1, d);
            const dayOfWeek = current.getDay();

            const sunday = new Date(current);
            sunday.setDate(current.getDate() - dayOfWeek);

            for (let i = 0; i < 7; i++) {
                const dateObj = new Date(sunday);
                dateObj.setDate(sunday.getDate() + i);
                const dateStr = `${dateObj.getFullYear()}-${pad(dateObj.getMonth() + 1)}-${pad(dateObj.getDate())}`;
                const dayConsults = this.filteredConsultations.filter(c => c.date === dateStr);

                days.push({
                    dayNumber: dateObj.getDate(),
                    dayName: this.dayNames[i],
                    date: dateStr,
                    isToday: dateStr === todayStr,
                    isSelected: dateStr === this.selectedDate,
                    consultations: dayConsults,
                });
            }
            return days;
        },

        prevWeek() {
            const [y, m, d] = this.selectedDate.split('-').map(Number);
            const dt = new Date(y, m - 1, d - 7);
            this.selectedDate = this.getLocalDateStr(dt);
            this.currentYear = dt.getFullYear();
            this.currentMonth = dt.getMonth();
        },

        nextWeek() {
            const [y, m, d] = this.selectedDate.split('-').map(Number);
            const dt = new Date(y, m - 1, d + 7);
            this.selectedDate = this.getLocalDateStr(dt);
            this.currentYear = dt.getFullYear();
            this.currentMonth = dt.getMonth();
        },

        prevDay() {
            const [y, m, d] = this.selectedDate.split('-').map(Number);
            const dt = new Date(y, m - 1, d - 1);
            this.selectedDate = this.getLocalDateStr(dt);
            this.currentYear = dt.getFullYear();
            this.currentMonth = dt.getMonth();
        },

        nextDay() {
            const [y, m, d] = this.selectedDate.split('-').map(Number);
            const dt = new Date(y, m - 1, d + 1);
            this.selectedDate = this.getLocalDateStr(dt);
            this.currentYear = dt.getFullYear();
            this.currentMonth = dt.getMonth();
        },

        statusBadgeClass(status) {
            switch(status) {
                case 'accepted':
                case 'scheduled':
                    return 'bg-emerald-50 text-emerald-800 border-emerald-200';
                case 'in_progress':
                    return 'bg-blue-50 text-blue-800 border-blue-200';
                case 'pending':
                    return 'bg-amber-50 text-amber-800 border-amber-200';
                case 'completed':
                    return 'bg-slate-100 text-slate-700 border-slate-200';
                case 'declined':
                case 'cancelled_by_client':
                case 'cancelled_by_vet':
                    return 'bg-rose-50 text-rose-800 border-rose-200';
                default:
                    return 'bg-slate-50 text-slate-700 border-slate-200';
            }
        }
    };
}
</script>
@endsection
