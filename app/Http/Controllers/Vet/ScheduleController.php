<?php

namespace App\Http\Controllers\Vet;

use App\Http\Controllers\Controller;
use App\Models\VetAvailability;
use App\Models\VetProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $profile = $user->vetProfile;
        $availabilities = VetAvailability::where('user_id', $user->id)->orderBy('day_of_week')->get();

        return view('vet.schedule.index', compact('user', 'profile', 'availabilities'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'consultation_fee' => 'required|numeric|min:0',
            'follow_up_fee' => 'nullable|numeric|min:0',
            'additional_pet_fee' => 'required|numeric|min:0',
            'additional_pet_duration' => 'required|integer|min:1|max:120',
            'clinic_name' => 'nullable|string|max:255',
            'clinic_address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'expertise' => 'required|string',
            'bio' => 'nullable|string',
            'languages' => 'nullable|string',
        ]);

        $profile = Auth::user()->vetProfile;
        if ($profile) {
            $profile->update($request->only([
                'consultation_fee', 'follow_up_fee', 'additional_pet_fee', 'additional_pet_duration',
                'clinic_name', 'clinic_address', 'city', 'province',
                'latitude', 'longitude', 'expertise', 'bio', 'languages'
            ]));
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile & consultation settings updated successfully.',
                'profile' => [
                    'consultation_fee' => $profile->consultation_fee,
                    'follow_up_fee' => $profile->follow_up_fee,
                    'additional_pet_fee' => $profile->additional_pet_fee,
                    'additional_pet_duration' => $profile->additional_pet_duration,
                    'clinic_name' => $profile->clinic_name,
                    'clinic_address' => $profile->clinic_address,
                    'city' => $profile->city,
                    'province' => $profile->province,
                    'has_coordinates' => ($profile->latitude && $profile->longitude),
                ],
            ]);
        }

        return back()->with('success', 'Profile & consultation settings updated successfully.');
    }

    public function storeAvailability(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        VetAvailability::updateOrCreate(
            ['user_id' => Auth::id(), 'day_of_week' => $request->day_of_week],
            [
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'is_active' => true,
            ]
        );

        return back()->with('success', 'Availability slot saved.');
    }

    public function deleteAvailability(VetAvailability $availability)
    {
        if ($availability->user_id !== Auth::id()) {
            abort(403);
        }
        $availability->delete();
        return back()->with('success', 'Slot removed.');
    }
}
