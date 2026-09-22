@extends('layouts.app')

@section('title', 'Log In')

@section('content')
<div class="max-w-md mx-auto my-12">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-brand-100 shadow-sm">
                <i class="fa-solid fa-user-lock text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Welcome Back</h1>
            <p class="text-slate-500 text-sm mt-1">Log in to your VetTeleconsult account</p>
        </div>

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

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
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

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center space-x-2 text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span>Remember me</span>
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
