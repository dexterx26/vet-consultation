<?php

namespace App\Http\Controllers\Vet;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Str;

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
            ->with(['client', 'pet', 'pet.animalType', 'pets', 'pets.animalType'])
            ->orderBy('scheduled_at', 'asc')
            ->get();

        $pendingRequestsData = $pendingRequests->map(function ($req) {
            return [
                'id' => $req->id,
                'consultation_number' => $req->consultation_number,
                'client_name' => $req->client->name ?? 'Client',
                'pet_names' => $req->all_pets->pluck('name')->join(', ') ?: ($req->pet->name ?? 'Patient'),
                'animal_icon' => ($req->pet && $req->pet->animalType) ? $req->pet->animalType->icon : 'fa-paw',
                'type' => $req->type,
                'scheduled_at_formatted' => $req->scheduled_at ? $req->scheduled_at->format('M d, Y @ g:i A') : 'Scheduled',
                'reason' => Str::limit($req->reason ?? '', 100),
                'accept_url' => route('vet.requests.accept', $req),
                'decline_url' => route('vet.requests.decline', $req),
                'show_url' => route('vet.requests.show', $req),
                'is_new' => false,
            ];
        });

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

        // Calendar Consultations for full calendar schedule view (hide declined and cancelled consultations)
        $calendarConsultations = Consultation::where('vet_id', $user->id)
            ->whereNotIn('status', ['declined', 'cancelled_by_client', 'cancelled_by_vet'])
            ->with(['client', 'pet', 'pet.animalType', 'pets', 'pets.animalType'])
            ->orderBy('scheduled_at', 'asc')
            ->get()
            ->map(function ($consult) {
                return [
                    'id' => $consult->id,
                    'consultation_number' => $consult->consultation_number,
                    'date' => $consult->scheduled_at ? $consult->scheduled_at->format('Y-m-d') : null,
                    'time' => $consult->scheduled_at ? $consult->scheduled_at->format('g:i A') : '',
                    'time_24' => $consult->scheduled_at ? $consult->scheduled_at->format('H:i') : '',
                    'client_name' => $consult->client->name ?? 'Client',
                    'pet_names' => $consult->all_pets->pluck('name')->join(', ') ?: ($consult->pet->name ?? 'Patient'),
                    'animal_icon' => ($consult->pet && $consult->pet->animalType) ? $consult->pet->animalType->icon : 'fa-paw',
                    'type' => $consult->type,
                    'status' => $consult->status,
                    'duration' => $consult->duration_minutes ?: 15,
                    'reason' => Str::limit($consult->reason ?? '', 90),
                    'video_url' => route('consultation.video', $consult),
                    'chat_url' => route('consultation.chat', $consult),
                    'show_url' => route('vet.requests.show', $consult),
                    'notes_url' => route('vet.records.create', $consult),
                ];
            });

        return view('vet.dashboard', compact(
            'user', 
            'profile', 
            'isPendingVerification', 
            'pendingRequests', 
            'pendingRequestsData',
            'todayConsultations', 
            'recentConsultations',
            'calendarConsultations'
        ));
    }

    public function toggleAvailability(Request $request)
    {
        $profile = Auth::user()->vetProfile;
        if ($profile) {
            $profile->is_available = !$profile->is_available;
            $profile->save();
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_available' => (bool) ($profile ? $profile->is_available : false),
                'message' => ($profile && $profile->is_available) ? 'You are now online for consultations.' : 'You are now offline.',
            ]);
        }

        return back()->with('success', 'Availability status updated.');
    }

    public function fetchPendingRequests(Request $request)
    {
        $user = Auth::user();

        $pendingRequests = Consultation::where('vet_id', $user->id)
            ->where('status', 'pending')
            ->with(['client', 'pet', 'pet.animalType', 'pets', 'pets.animalType'])
            ->orderBy('scheduled_at', 'asc')
            ->get();

        $data = $pendingRequests->map(function ($req) {
            return [
                'id' => $req->id,
                'consultation_number' => $req->consultation_number,
                'client_name' => $req->client->name ?? 'Client',
                'pet_names' => $req->all_pets->pluck('name')->join(', ') ?: ($req->pet->name ?? 'Patient'),
                'animal_icon' => ($req->pet && $req->pet->animalType) ? $req->pet->animalType->icon : 'fa-paw',
                'type' => $req->type,
                'status' => 'pending',
                'scheduled_at_formatted' => $req->scheduled_at ? $req->scheduled_at->format('M d, Y @ g:i A') : 'Scheduled',
                'date' => $req->scheduled_at ? $req->scheduled_at->format('Y-m-d') : null,
                'time' => $req->scheduled_at ? $req->scheduled_at->format('g:i A') : '',
                'time_24' => $req->scheduled_at ? $req->scheduled_at->format('H:i') : '',
                'duration_minutes' => $req->duration_minutes ?: 15,
                'reason' => Str::limit($req->reason ?? '', 100),
                'accept_url' => route('vet.requests.accept', $req),
                'decline_url' => route('vet.requests.decline', $req),
                'show_url' => route('vet.requests.show', $req),
                'video_url' => route('consultation.video', $req),
                'chat_url' => route('consultation.chat', $req),
                'notes_url' => route('vet.records.create', $req),
                'is_new' => false,
            ];
        });

        return response()->json([
            'count' => $data->count(),
            'pending_requests' => $data,
        ]);
    }
}
