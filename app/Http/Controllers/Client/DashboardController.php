<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $pets = $user->pets()->with(['animalType', 'breed', 'consultations.vet', 'consultations.record'])->get();

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
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        $credits = Cache::remember("user_{$user->id}_credits", 120, function () use ($user) {
            return (int) ($user->fresh()->credits ?? 0);
        });

        return response()->json([
            'status' => 'success',
            'credits' => $credits,
            'formatted' => number_format($credits),
        ]);
    }
}
