<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ClientProfile;
use App\Models\VetProfile;
use App\Models\VetDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->status === 'suspended') {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account has been suspended by an administrator.']);
            }

            return $this->redirectBasedOnRole($user);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegisterClient()
    {
        return view('auth.register-client');
    }

    public function registerClient(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'city' => ['required', 'string'],
            'province' => ['required', 'string'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => 'client',
            'status' => 'active',
            'password' => Hash::make($request->password),
        ]);

        // Mock coordinates based on city or standard defaults
        $lat = 14.6760;
        $lng = 121.0437;
        if (str_contains(strtolower($request->city), 'manila')) {
            $lat = 14.5995; $lng = 120.9842;
        } elseif (str_contains(strtolower($request->city), 'makati')) {
            $lat = 14.5547; $lng = 121.0244;
        }

        ClientProfile::create([
            'user_id' => $user->id,
            'address' => $request->address ?? '',
            'city' => $request->city,
            'province' => $request->province,
            'country' => 'Philippines',
            'latitude' => $lat,
            'longitude' => $lng,
        ]);

        Auth::login($user);

        return redirect()->route('client.dashboard')->with('success', 'Account created successfully! Welcome to VetTeleconsult.');
    }

    public function showRegisterVet()
    {
        return view('auth.register-vet');
    }

    public function registerVet(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'license_number' => ['required', 'string', 'unique:vet_profiles'],
            'clinic_name' => ['nullable', 'string'],
            'years_experience' => ['required', 'integer', 'min:0'],
            'expertise' => ['required', 'string'],
            'animals_handled' => ['required', 'array'],
            'consultation_fee' => ['required', 'numeric', 'min:0'],
            'city' => ['required', 'string'],
            'province' => ['required', 'string'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => 'veterinarian',
            'status' => 'pending', // Requires admin verification
            'password' => Hash::make($request->password),
        ]);

        $docPath = 'documents/license_' . time() . '.' . $request->file('document')->getClientOriginalExtension();
        $request->file('document')->storeAs('public', $docPath);

        VetProfile::create([
            'user_id' => $user->id,
            'license_number' => $request->license_number,
            'clinic_name' => $request->clinic_name,
            'clinic_address' => $request->clinic_address ?? '',
            'years_experience' => $request->years_experience,
            'expertise' => $request->expertise,
            'animals_handled' => $request->animals_handled,
            'consultation_fee' => $request->consultation_fee,
            'bio' => $request->bio ?? '',
            'languages' => $request->languages ?? 'English, Tagalog',
            'city' => $request->city,
            'province' => $request->province,
            'latitude' => 14.6500,
            'longitude' => 121.0500,
            'is_available' => false,
        ]);

        VetDocument::create([
            'user_id' => $user->id,
            'document_type' => 'professional_license',
            'document_name' => $request->file('document')->getClientOriginalName(),
            'file_path' => $docPath,
            'status' => 'pending',
            'admin_notes' => 'Submitted during registration.',
        ]);

        Auth::login($user);

        return redirect()->route('vet.dashboard')->with('info', 'Registration submitted! Your veterinarian account is currently pending verification by our administrative team.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function redirectBasedOnRole($user)
    {
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isVet()) {
            return redirect()->route('vet.dashboard');
        } else {
            return redirect()->route('client.dashboard');
        }
    }
}
