<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $pets = $user->pets()->with('animalType', 'breed')->get();

        $upcomingConsultations = Consultation::where('client_id', $user->id)
            ->whereIn('status', ['accepted', 'scheduled', 'in_progress'])
            ->with(['vet', 'vet.vetProfile', 'pet'])
            ->orderBy('scheduled_at', 'asc')
            ->get();

        $recentConsultations = Consultation::where('client_id', $user->id)
            ->whereIn('status', ['completed', 'cancelled_by_client', 'cancelled_by_vet', 'declined'])
            ->with(['vet', 'vet.vetProfile', 'pet', 'record'])
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        return view('client.dashboard', compact('pets', 'upcomingConsultations', 'recentConsultations'));
    }

    public function getCredits()
    {
        $user = Auth::user();
        $credits = (int) ($user ? ($user->fresh()->credits ?? 0) : 0);
        return response()->json([
            'status' => 'success',
            'credits' => $credits,
            'formatted' => number_format($credits),
        ]);
    }
}
