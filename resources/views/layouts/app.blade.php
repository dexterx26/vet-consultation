<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'VetTeleconsult') — Veterinary Teleconsultation Platform</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS CDN for instant, vibrant styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    screens: {
                        'xs': '420px',
                    },
                    colors: {
                        brand: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        },
                        navy: {
                            800: '#0f172a',
                            900: '#0b0f19',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        h1, h2, h3, h4, h5, h6, .font-heading { font-family: 'Outfit', sans-serif; }
        .glass-nav { background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(12px); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>

    @stack('styles')
</head>
<body class="min-h-screen flex flex-col antialiased bg-slate-50">

    <!-- Main Top Navigation -->
    <nav class="glass-nav text-white sticky top-0 z-50 border-b border-slate-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                
                <!-- Logo & Brand Name -->
                <div class="flex items-center space-x-3">
                    <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-teal-400 flex items-center justify-center shadow-md shadow-brand-500/20 group-hover:scale-105 transition-transform">
                            <i class="fa-solid fa-stethoscope text-white text-lg"></i>
                        </div>
                        <div>
                            <span class="font-heading font-extrabold text-xl tracking-tight text-white group-hover:text-brand-400 transition-colors">VetTeleconsult</span>
                            <span class="block text-[10px] uppercase font-semibold text-brand-400 tracking-wider">Veterinary Care Platform</span>
                        </div>
                    </a>
                </div>

                @auth
                <!-- Center Navigation Links based on Role -->
                <div class="hidden md:flex items-center space-x-1 text-sm font-medium">
                    @if(auth()->user()->isClient())
                        <a href="{{ route('client.dashboard') }}" class="px-3.5 py-2 rounded-lg transition-colors {{ request()->routeIs('client.dashboard') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-house mr-1.5 opacity-80"></i> Dashboard
                        </a>
                        <a href="{{ route('client.pets.index') }}" class="px-3.5 py-2 rounded-lg transition-colors {{ request()->routeIs('client.pets.*') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-paw mr-1.5 opacity-80"></i> My Pets
                        </a>
                        <a href="{{ route('client.vets.search') }}" class="px-3.5 py-2 rounded-lg transition-colors {{ request()->routeIs('client.vets.*') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-user-doctor mr-1.5 opacity-80"></i> Find Vets
                        </a>
                        <a href="{{ route('client.bookings.index') }}" class="px-3.5 py-2 rounded-lg transition-colors {{ request()->routeIs('client.bookings.*') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-calendar-check mr-1.5 opacity-80"></i> Consultations
                        </a>
                    @elseif(auth()->user()->isVet())
                        <a href="{{ route('vet.dashboard') }}" class="px-3.5 py-2 rounded-lg transition-colors {{ request()->routeIs('vet.dashboard') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-chart-line mr-1.5 opacity-80"></i> Vet Dashboard
                        </a>
                        <a href="{{ route('vet.requests.index') }}" class="px-3.5 py-2 rounded-lg transition-colors {{ request()->routeIs('vet.requests.*') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-notes-medical mr-1.5 opacity-80"></i> Consultation Requests
                        </a>
                        <a href="{{ route('vet.schedule.index') }}" class="px-3.5 py-2 rounded-lg transition-colors {{ request()->routeIs('vet.schedule.*') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-clock mr-1.5 opacity-80"></i> Schedule & Fees
                        </a>
                    @elseif(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="px-3.5 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-gauge-high mr-1.5 opacity-80"></i> Dashboard
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="px-3.5 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.users.*') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-users mr-1.5 opacity-80"></i> Users & Credits
                        </a>
                        <a href="{{ route('admin.vets.index') }}" class="px-3.5 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.vets.*') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-user-check mr-1.5 opacity-80"></i> Vets
                        </a>
                        <a href="{{ route('admin.consultations.index') }}" class="px-3.5 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.consultations.*') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-folder-tree mr-1.5 opacity-80"></i> Consultations
                        </a>
                        <a href="{{ route('admin.settings.index') }}" class="px-3.5 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.settings.*') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-gear mr-1.5 opacity-80"></i> Settings
                        </a>
                    @endif
                </div>

                <!-- Right Menu & User Badge -->
                <div class="flex items-center space-x-2 sm:space-x-3" x-data="{ openDropdown: false }">
                    @if(auth()->user()->isClient())
                        <!-- Real-Time Client Credits Badge in Navbar -->
                        <div x-data="clientCreditsNavbar({{ auth()->user()->credits ?? 0 }})"
                             x-init="init()"
                             class="flex items-center space-x-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl bg-amber-400/15 border border-amber-400/30 text-amber-300 text-xs font-extrabold shadow-sm transition-all duration-300 select-none"
                             :class="justUpdated ? 'ring-2 ring-amber-400 bg-amber-400/30 scale-105 shadow-md shadow-amber-400/30 text-amber-200' : ''"
                             title="Your Current Consultation Credits Balance">
                            <i class="fa-solid fa-coins text-amber-400 text-xs sm:text-sm" :class="justUpdated ? 'animate-bounce' : ''"></i>
                            <span x-text="formattedCredits + ' Credits'">{{ number_format(auth()->user()->credits ?? 0) }} Credits</span>
                        </div>
                    @endif

                    <div class="relative">
                        <button @click="openDropdown = !openDropdown" class="flex items-center space-x-3 p-1.5 rounded-xl hover:bg-slate-800 transition-colors focus:outline-none">
                            <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-brand-400 font-bold border border-slate-600">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="hidden sm:block text-left text-xs">
                                <p class="font-semibold text-white leading-none">{{ auth()->user()->name }}</p>
                                <span class="text-[10px] capitalize text-brand-400 font-medium">{{ auth()->user()->role }}</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                        </button>

                        <div x-show="openDropdown" @click.outside="openDropdown = false" x-transition class="absolute right-0 mt-2 w-48 bg-slate-900 border border-slate-800 rounded-xl shadow-xl py-2 z-50 text-xs">
                            <div class="px-4 py-2 border-b border-slate-800">
                                <p class="text-white font-medium truncate">{{ auth()->user()->email }}</p>
                                @if(auth()->user()->isClient())
                                    <div class="text-[11px] text-amber-400 font-bold mt-1 flex items-center space-x-1"
                                         x-data="{ credits: {{ auth()->user()->credits ?? 0 }} }"
                                         @credits-updated.window="credits = $event.detail.credits">
                                        <i class="fa-solid fa-coins"></i>
                                        <span x-text="new Intl.NumberFormat().format(credits) + ' Credits'">{{ number_format(auth()->user()->credits ?? 0) }} Credits</span>
                                    </div>
                                @else
                                    <span class="text-[10px] text-slate-400 capitalize">Status: {{ auth()->user()->status }}</span>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-rose-400 hover:bg-slate-800 flex items-center space-x-2">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                    <span>Sign Out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @else
                <div class="flex items-center space-x-3 text-sm">
                    <a href="{{ route('login') }}" class="text-slate-300 hover:text-white font-medium px-3 py-1.5">Log In</a>
                    <a href="{{ route('register.client') }}" class="bg-brand-600 hover:bg-brand-500 text-white font-semibold px-4 py-2 rounded-lg shadow-md shadow-brand-600/30 transition-all">Client Sign Up</a>
                    <a href="{{ route('register.vet') }}" class="border border-brand-500 text-brand-400 hover:bg-brand-500/10 font-semibold px-4 py-2 rounded-lg transition-all">Join as Vet</a>
                </div>
                @endauth

            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl shadow-sm flex items-center justify-between" x-data="{ show: true }" x-show="show">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-500 hover:text-emerald-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl shadow-sm flex items-center justify-between" x-data="{ show: true }" x-show="show">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i>
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-rose-500 hover:text-rose-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if(session('info'))
            <div class="bg-sky-50 border border-sky-200 text-sky-800 px-4 py-3 rounded-xl shadow-sm flex items-center justify-between" x-data="{ show: true }" x-show="show">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-circle-info text-sky-500 text-lg"></i>
                    <span class="text-sm font-medium">{{ session('info') }}</span>
                </div>
                <button @click="show = false" class="text-sky-500 hover:text-sky-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif
    </div>

    <!-- Main Content Container -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-navy-800 text-slate-400 text-xs py-8 border-t border-slate-800 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-7 h-7 rounded-lg bg-brand-600 flex items-center justify-center text-white">
                    <i class="fa-solid fa-stethoscope text-xs"></i>
                </div>
                <span class="font-heading font-bold text-slate-200 text-sm">VetTeleconsult Platform</span>
            </div>
            <p>© {{ date('Y') }} Veterinary Teleconsultation System. API-First Web & Mobile Backend.</p>
            <div class="flex items-center space-x-4 text-slate-400">
                <span>Phase 1 Web Application</span>
                <span>•</span>
                <span>Mobile Ready</span>
            </div>
        </div>
    </footer>

    @auth
        @if(auth()->user()->isClient())
        <script>
            function clientCreditsNavbar(initialCredits) {
                return {
                    credits: initialCredits || 0,
                    justUpdated: false,
                    pollTimer: null,

                    get formattedCredits() {
                        return new Intl.NumberFormat().format(this.credits);
                    },

                    init() {
                        // Listen for in-app instant updates (e.g. video call / chat heartbeat / approvals)
                        window.addEventListener('credits-updated', (e) => {
                            if (e.detail && e.detail.credits !== undefined) {
                                this.updateCredits(parseInt(e.detail.credits));
                            }
                        });

                        // Periodic polling every 4 seconds to sync credits from backend
                        this.pollTimer = setInterval(() => {
                            this.fetchCredits();
                        }, 4000);

                        // Sync immediately when browser tab regains focus
                        window.addEventListener('focus', () => {
                            this.fetchCredits();
                        });
                    },

                    fetchCredits() {
                        fetch('{{ route('client.credits-balance') }}', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success' && data.credits !== undefined) {
                                this.updateCredits(parseInt(data.credits));
                            }
                        })
                        .catch(() => {});
                    },

                    updateCredits(newCredits) {
                        if (this.credits !== newCredits) {
                            this.credits = newCredits;
                            this.justUpdated = true;
                            // Broadcast so other client credit indicators update in sync
                            window.dispatchEvent(new CustomEvent('credits-updated', { detail: { credits: newCredits } }));
                            setTimeout(() => {
                                this.justUpdated = false;
                            }, 1800);
                        }
                    }
                };
            }
        </script>
        @endif
    @endauth

    @stack('scripts')
</body>
</html>
