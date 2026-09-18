<?php

namespace App\Http\Controllers\Vet;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $profile = $user->vetProfile;

        // Pending verification check banner status
        $isPendingVerification = ($user->status === 'pending');

        $pendingRequests = Consultation::where('vet_id', $user->id)
            ->where('status', 'pending')
            ->with(['client', 'pet', 'pet.animalType'])
            ->orderBy('scheduled_at', 'asc')
            ->get();

        $todayConsultations = Consultation::where('vet_id', $user->id)
            ->whereIn('status', ['accepted', 'scheduled', 'in_progress'])
            ->whereDate('scheduled_at', today())
            ->with(['client', 'pet', 'pet.animalType'])
            ->orderBy('scheduled_at', 'asc')
            ->get();

        $recentConsultations = Consultation::where('vet_id', $user->id)
            ->whereIn('status', ['completed', 'cancelled_by_client', 'cancelled_by_vet', 'declined'])
            ->with(['client', 'pet', 'record'])
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        return view('vet.dashboard', compact('user', 'profile', 'isPendingVerification', 'pendingRequests', 'todayConsultations', 'recentConsultations'));
    }

    public function toggleAvailability(Request $request)
    {
        $profile = Auth::user()->vetProfile;
        if ($profile) {
            $profile->is_available = !$profile->is_available;
            $profile->save();
        }

        return back()->with('success', 'Availability status updated.');
    }
}
