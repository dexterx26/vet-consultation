<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Consultation;
use App\Models\VetDocument;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalVets = User::where('role', 'veterinarian')->count();
        $pendingVets = User::where('role', 'veterinarian')->where('status', 'pending')->count();
        $totalClients = User::where('role', 'client')->count();
        $totalConsultations = Consultation::count();
        $todayConsultations = Consultation::whereDate('created_at', today())->count();
        $activeCalls = Consultation::where('status', 'in_progress')->count();

        $recentVetApplications = User::where('role', 'veterinarian')
            ->where('status', 'pending')
            ->with(['vetProfile', 'vetDocuments'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $recentConsultations = Consultation::with(['client', 'vet', 'pet'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalVets', 'pendingVets', 'totalClients', 'totalConsultations',
            'todayConsultations', 'activeCalls', 'recentVetApplications', 'recentConsultations'
        ));
    }
}
