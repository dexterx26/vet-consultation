@extends('layouts.app')

@section('title', 'Log In')

@section('content')
<div class="max-w-md mx-auto my-12 px-4 sm:px-0">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-6 sm:p-8">
        <div class="text-center mb-6">
            <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-brand-100 shadow-sm">
                <i class="fa-solid fa-user-lock text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Welcome Back</h1>
            <p class="text-slate-500 text-sm mt-1">Log in to your VetTeleconsult account</p>
        </div>

        {{-- Session Terminated Notification (From Another Device Login) --}}
        @if(request('reason') === 'dual_login_terminated')
            <div class="mb-6 bg-rose-50 border border-rose-200 rounded-xl p-4 text-xs text-rose-800 shadow-sm animate-fadeIn">
                <div class="flex items-start space-x-3">
                    <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-circle-exclamation text-sm"></i>
                    </div>
                    <div>
                        <p class="font-bold text-rose-900 text-sm">Signed Out by Another Device</p>
                        <p class="text-rose-700 mt-0.5 leading-relaxed">
                            Your session ended because this account was logged into on another device. Dual login is restricted on this platform.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Success / Info Flash Notification --}}
        @if(session('info'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 rounded-xl p-3.5 text-xs text-emerald-800 flex items-center space-x-2.5 shadow-sm">
                <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                <span class="font-medium">{{ session('info') }}</span>
            </div>
        @endif

        {{-- DUAL LOGIN ACTIVE SESSION DETECTED CARD --}}
        @if(session('dual_login_conflict'))
            @php $conflict = session('dual_login_conflict'); @endphp
            <div class="mb-6 bg-amber-50/90 border border-amber-200 rounded-2xl p-5 text-xs text-slate-700 shadow-md">
                <div class="flex items-center space-x-2.5 text-amber-800 font-bold text-sm mb-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-shield-halved"></i>
                    </span>
                    <span>Active Session Detected</span>
                </div>
                
                <p class="text-slate-600 mb-3.5 leading-relaxed">
                    This account is currently logged into on another device. Dual login is not permitted to preserve medical consultation privacy and security.
                </p>

                <!-- Conflicting Device Info Card -->
                <div class="bg-white border border-amber-200/80 rounded-xl p-3.5 mb-4 shadow-sm space-y-2">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0 text-base">
                            <i class="{{ $conflict['icon'] ?? 'fa-solid fa-laptop' }} text-brand-600"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-800 text-xs truncate">{{ $conflict['device'] ?? 'Active Device' }}</p>
                            <p class="text-[11px] text-slate-500 font-mono">IP: {{ $conflict['ip_address'] ?? 'Unknown' }}</p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200 shrink-0">
                            {{ $conflict['last_activity'] ?? 'Active' }}
                        </span>
                    </div>
                </div>

                <!-- Force Logout & Sign In Action -->
                <form method="POST" action="{{ route('login.force') }}" class="space-y-2">
                    @csrf
                    <input type="hidden" name="token" value="{{ $conflict['token'] }}">

                    <button type="submit" class="w-full bg-gradient-to-r from-amber-600 to-rose-600 hover:from-amber-700 hover:to-rose-700 text-white font-semibold py-2.5 px-4 rounded-xl shadow-md shadow-rose-600/20 transition-all text-xs flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Log Out Other Device & Sign In Here</span>
                    </button>
                    
                    <a href="{{ route('login') }}" class="block text-center text-[11px] text-slate-500 hover:text-slate-700 py-1 font-medium transition-colors">
                        Cancel and keep existing session
                    </a>
                </form>
            </div>
        @endif

        <!-- Quick Demo Credentials Box -->
        <div class="mb-6 bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs">
            <p class="font-bold text-slate-700 mb-2 flex items-center">
                <i class="fa-solid fa-lightbulb text-amber-500 mr-1.5 text-sm"></i> Demo Quick Credentials (Password: <code class="bg-slate-200 px-1 py-0.5 rounded text-slate-800">password</code>)
            </p>
            <div class="space-y-1 text-slate-600">
                <p><strong class="text-brand-700">Client:</strong> <code class="select-all">client@gmail.com</code></p>
                <p><strong class="text-brand-700">Approved Vet:</strong> <code class="select-all">dr.maria@vetconsult.com</code></p>
                <p><strong class="text-brand-700">Pending Vet:</strong> <code class="select-all">dr.angela@vetconsult.com</code></p>
                <p><strong class="text-brand-700">Administrator:</strong> <code class="select-all">admin@vetconsult.com</code></p>
            </div>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input type="email" name="email" id="email" value="{{ old('email', 'client@gmail.com') }}" required autofocus
                        class="pl-10 pr-3.5 w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                </div>
                @error('email')
                    <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="password" id="password" value="password" required
                        class="pl-10 pr-3.5 w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                </div>
                @error('password')
                    <p class="text-rose-500 text-xs mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center space-x-2 text-slate-600 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span>Remember me</span>
                </label>

                <label class="flex items-center space-x-1.5 text-slate-500 hover:text-slate-700 cursor-pointer" title="Disconnect any existing active session on other devices during login">
                    <input type="checkbox" name="force_logout" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                    <span class="text-[11px]">Log out other devices</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-semibold py-3 rounded-xl shadow-md shadow-brand-600/30 transition-all text-sm flex items-center justify-center space-x-2">
                <span>Sign In to Platform</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100 text-center text-xs text-slate-500 space-y-2">
            <p>Don't have an account yet?</p>
            <div class="flex items-center justify-center space-x-4">
                <a href="{{ route('register.client') }}" class="text-brand-600 font-semibold hover:underline">Register as Pet Owner</a>
                <span>•</span>
                <a href="{{ route('register.vet') }}" class="text-brand-600 font-semibold hover:underline">Register as Vet</a>
            </div>
        </div>
    </div>
</div>
@endsection
