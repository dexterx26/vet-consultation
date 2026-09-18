<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pet;
use App\Models\Consultation;
use App\Models\ConsultationMessage;
use App\Models\AnimalType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiController extends Controller
{
    // Auth API
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
            'token' => $token,
        ]);
    }

    // Vet Search API
    public function getVets(Request $request)
    {
        $lat = $request->get('latitude', 14.6760);
        $lng = $request->get('longitude', 121.0437);

        $vets = User::where('role', 'veterinarian')
            ->where('status', 'active')
            ->with(['vetProfile', 'vetProfile.reviews'])
            ->get();

        $vets->transform(function ($vet) use ($lat, $lng) {
            $profile = $vet->vetProfile;
            $dist = 5.0;
            if ($profile && $profile->latitude && $profile->longitude) {
                $dLat = deg2rad($profile->latitude - $lat);
                $dLon = deg2rad($profile->longitude - $lng);
                $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat)) * cos(deg2rad($profile->latitude)) * sin($dLon / 2) * sin($dLon / 2);
                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                $dist = round(6371 * $c, 1);
            }
            return [
                'id' => $vet->id,
                'name' => $vet->name,
                'email' => $vet->email,
                'phone' => $vet->phone,
                'clinic_name' => $profile->clinic_name ?? '',
                'clinic_address' => $profile->clinic_address ?? '',
                'years_experience' => $profile->years_experience ?? 0,
                'expertise' => $profile->expertise ?? '',
                'animals_handled' => $profile->animals_handled ?? [],
                'consultation_fee' => $profile->consultation_fee ?? 500,
                'rating' => $profile ? $profile->average_rating : 5.0,
                'distance_km' => $dist,
                'city' => $profile->city ?? '',
            ];
        });

        return response()->json(['status' => 'success', 'data' => $vets]);
    }

    // Pets API
    public function getPets(Request $request)
    {
        $pets = $request->user()->pets()->with('animalType', 'breed')->get();
        return response()->json(['status' => 'success', 'data' => $pets]);
    }

    // Consultations API
    public function getConsultations(Request $request)
    {
        $user = $request->user();
        $query = Consultation::with(['client', 'vet', 'pet', 'record']);

        if ($user->isVet()) {
            $query->where('vet_id', $user->id);
        } else {
            $query->where('client_id', $user->id);
        }

        $consultations = $query->orderBy('created_at', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $consultations]);
    }
}
