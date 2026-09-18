@extends('layouts.app')

@section('title', 'Client Registration')

@section('content')
<div class="max-w-xl mx-auto my-8">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-brand-100 shadow-sm">
                <i class="fa-solid fa-user-plus text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Pet Owner Registration</h1>
            <p class="text-slate-500 text-sm mt-1">Create an account to book teleconsultations & manage your pets</p>
        </div>

        <form method="POST" action="{{ route('register.client') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Full Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="e.g. Maria Clara"
                    class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                @error('name') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required placeholder="name@example.com"
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                    @error('email') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Mobile Number</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required placeholder="09171234567"
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                    @error('phone') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="city" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">City / Municipality</label>
                    <input type="text" name="city" id="city" value="{{ old('city', 'Quezon City') }}" required placeholder="e.g. Quezon City"
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                    @error('city') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="province" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Province</label>
                    <input type="text" name="province" id="province" value="{{ old('province', 'Metro Manila') }}" required placeholder="e.g. Metro Manila"
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                    @error('province') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="address" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Street Address (Optional)</label>
                <input type="text" name="address" id="address" value="{{ old('address') }}" placeholder="House / Unit No., Street, Subdivision"
                    class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password</label>
                    <input type="password" name="password" id="password" required
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                    @error('password') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                        class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm py-2.5 shadow-sm">
                </div>
            </div>

            <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-semibold py-3 rounded-xl shadow-md shadow-brand-600/30 transition-all text-sm mt-4">
                Create Pet Owner Account
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-500">
            Already registered? <a href="{{ route('login') }}" class="text-brand-600 font-semibold hover:underline">Log In</a>
        </div>
    </div>
</div>
@endsection
