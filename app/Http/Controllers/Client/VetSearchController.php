<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VetProfile;
use App\Models\AnimalType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VetSearchController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $clientProfile = $user->clientProfile;

        $clientLat = $clientProfile ? ($clientProfile->latitude ?: 14.6760) : 14.6760;
        $clientLng = $clientProfile ? ($clientProfile->longitude ?: 121.0437) : 121.0437;

        $animalTypes = AnimalType::where('is_active', true)->get();

        $query = User::where('role', 'veterinarian')
            ->where('status', 'active')
            ->with(['vetProfile', 'vetProfile.reviews.client']);

        // Filter by Animal Type
        if ($request->filled('animal_type')) {
            $animal = $request->animal_type;
            $query->whereHas('vetProfile', function ($q) use ($animal) {
                $q->where('animals_handled', 'like', '%' . $animal . '%');
            });
        }

        // Filter by Max Fee
        if ($request->filled('max_fee')) {
            $maxFee = $request->max_fee;
            $query->whereHas('vetProfile', function ($q) use ($maxFee) {
                $q->where('consultation_fee', '<=', $maxFee);
            });
        }

        // Filter by Keyword / Specialization / Name
        if ($request->filled('keyword')) {
            $keyword = '%' . $request->keyword . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', $keyword)
                  ->orWhereHas('vetProfile', function ($vq) use ($keyword) {
                      $vq->where('expertise', 'like', $keyword)
                         ->orWhere('clinic_name', 'like', $keyword);
                  });
            });
        }

        $vets = $query->get();

        // Calculate proximity distance using Haversine formula
        $vets->transform(function ($vet) use ($clientLat, $clientLng) {
            $profile = $vet->vetProfile;
            if ($profile && $profile->latitude && $profile->longitude) {
                $vetLat = $profile->latitude;
                $vetLng = $profile->longitude;
                $earthRadius = 6371; // km

                $dLat = deg2rad($vetLat - $clientLat);
                $dLon = deg2rad($vetLng - $clientLng);

                $a = sin($dLat / 2) * sin($dLat / 2) +
                     cos(deg2rad($clientLat)) * cos(deg2rad($vetLat)) *
                     sin($dLon / 2) * sin($dLon / 2);

                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                $distance = $earthRadius * $c;

                $vet->distance_km = round($distance, 1);
            } else {
                $vet->distance_km = 5.0; // fallback approx
            }

            return $vet;
        });

        // Sort by distance by default
        $sort = $request->get('sort', 'distance');
        if ($sort === 'distance') {
            $vets = $vets->sortBy('distance_km')->values();
        } elseif ($sort === 'rating') {
            $vets = $vets->sortByDesc(function ($vet) {
                return $vet->vetProfile ? $vet->vetProfile->average_rating : 0;
            })->values();
        } elseif ($sort === 'fee_asc') {
            $vets = $vets->sortBy(function ($vet) {
                return $vet->vetProfile ? $vet->vetProfile->consultation_fee : 0;
            })->values();
        }

        return view('client.vets.search', compact('vets', 'animalTypes', 'clientProfile'));
    }

    public function show(User $vet)
    {
        if (!$vet->isVet() || $vet->status !== 'active') {
            abort(404, 'Veterinarian profile not found.');
        }

        $vet->load(['vetProfile', 'vetProfile.reviews.client', 'vetAvailabilities']);
        $clientPets = Auth::user()->pets;

        return view('client.vets.show', compact('vet', 'clientPets'));
    }
}
