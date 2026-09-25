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

use App\Services\DeviceDetector;
use App\Events\UserSessionTerminated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
            'force_logout' => ['nullable', 'boolean'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Check credentials
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        // Account status check
        if ($user->status === 'suspended') {
            return back()->withErrors(['email' => 'Your account has been suspended by an administrator.']);
        }

        // Clean up expired sessions for this user
        $lifetimeSeconds = config('session.lifetime', 120) * 60;
        $cutoff = time() - $lifetimeSeconds;
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('last_activity', '<', $cutoff)
            ->delete();

        // Check for existing active session on another device/browser
        $activeOtherSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->where('last_activity', '>=', $cutoff)
            ->orderByDesc('last_activity')
            ->get();

        // DUAL LOGIN PREVENTION: If active session exists and user has NOT requested force logout
        if ($activeOtherSessions->isNotEmpty() && ! $request->boolean('force_logout')) {
            $conflictSession = $activeOtherSessions->first();
            $deviceInfo = DeviceDetector::parse($conflictSession->user_agent);

            // Store temporary one-time confirmation token in session (valid for 5 mins)
            $pendingToken = Str::random(40);
            $request->session()->put('dual_login_pending', [
                'user_id' => $user->id,
                'token' => $pendingToken,
                'remember' => $request->boolean('remember'),
                'expires_at' => time() + 300,
            ]);

            return back()->withInput($request->only('email'))->with('dual_login_conflict', [
                'device' => $deviceInfo['full'],
                'platform' => $deviceInfo['platform'],
                'browser' => $deviceInfo['browser'],
                'icon' => $deviceInfo['icon'],
                'ip_address' => $conflictSession->ip_address ?: 'Unknown IP',
                'last_activity' => Carbon::createFromTimestamp($conflictSession->last_activity)->diffForHumans(),
                'token' => $pendingToken,
            ]);
        }

        // Force logout requested or no concurrent session
        if ($activeOtherSessions->isNotEmpty() && $request->boolean('force_logout')) {
            $this->terminateOtherSessions($user, $request);
        }

        // Authenticate user
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->forget('dual_login_pending');

        $redirect = $this->redirectBasedOnRole($user);
        if ($activeOtherSessions->isNotEmpty() && $request->boolean('force_logout')) {
            return $redirect->with('info', 'Logged in successfully. Your previous session on other devices has been terminated.');
        }

        return $redirect;
    }

    /**
     * Force logout existing active device and authenticate immediately via confirmed token.
     */
    public function forceLogoutAndLogin(Request $request)
    {
        $pending = $request->session()->get('dual_login_pending');

        if (! $pending || ! hash_equals($pending['token'] ?? '', (string) $request->input('token')) || time() > ($pending['expires_at'] ?? 0)) {
            $request->session()->forget('dual_login_pending');
            return redirect()->route('login')->withErrors([
                'email' => 'Session confirmation timed out. Please enter your credentials again.',
            ]);
        }

        $user = User::find($pending['user_id']);
        if (! $user || $user->status === 'suspended') {
            $request->session()->forget('dual_login_pending');
            return redirect()->route('login')->withErrors([
                'email' => 'Unable to authenticate with this account.',
            ]);
        }

        // Terminate other sessions and notify them
        $this->terminateOtherSessions($user, $request);

        // Log in user on current device
        Auth::login($user, (bool) ($pending['remember'] ?? false));
        $request->session()->regenerate();
        $request->session()->forget('dual_login_pending');

        return $this->redirectBasedOnRole($user)->with('info', 'Logged in successfully. Your other active session has been logged out.');
    }

    /**
     * Invalidate and broadcast termination for other active sessions of the user.
     */
    protected function terminateOtherSessions(User $user, Request $request): void
    {
        $currentDevice = DeviceDetector::parse($request->userAgent())['full'];

        try {
            broadcast(new UserSessionTerminated($user->id, 'dual_login', $currentDevice))->toOthers();
        } catch (\Throwable $e) {
            // Keep going even if Reverb server is offline
        }

        // Invalidate other sessions in database
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        // Invalidate old remember-me cookies on other devices
        $user->forceFill([
            'remember_token' => Str::random(60),
        ])->save();
    }

    /**
     * Check current session status for active device enforcement.
     */
    public function checkSessionStatus(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'active' => false,
                'reason' => 'unauthenticated',
                'message' => 'You are not logged in.',
            ]);
        }

        $sessionId = $request->session()->getId();
        $exists = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->exists();

        if (! $exists) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'active' => false,
                'reason' => 'dual_login_terminated',
                'message' => 'Your session was ended because this account was logged into on another device.',
            ]);
        }

        return response()->json([
            'active' => true,
        ]);
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
        $sessionId = $request->session()->getId();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($sessionId) {
            DB::table('sessions')->where('id', $sessionId)->delete();
        }

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
