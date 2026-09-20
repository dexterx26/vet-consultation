<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VetDocument;
use App\Models\AppNotification;
use Illuminate\Http\Request;

class VetVerificationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');

        $query = User::where('role', 'veterinarian')->with(['vetProfile', 'vetDocuments']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $vets = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.vets.index', compact('vets', 'status'));
    }

    public function show(User $vet)
    {
        if (!$vet->isVet()) {
            abort(404);
        }

        $vet->load(['vetProfile', 'vetDocuments']);
        return view('admin.vets.show', compact('vet'));
    }

    public function approve(User $vet)
    {
        if (!$vet->isVet()) {
            abort(404);
        }

        $vet->update(['status' => 'active']);

        if ($vet->vetProfile) {
            $vet->vetProfile->update(['is_available' => true]);
        }

        VetDocument::where('user_id', $vet->id)->update([
            'status' => 'verified',
            'verified_at' => now(),
            'admin_notes' => 'Verified and approved by administrator.'
        ]);

        AppNotification::create([
            'user_id' => $vet->id,
            'title' => 'Veterinarian Account Approved! 🎉',
            'message' => 'Congratulations! Your veterinarian profile and professional credentials have been verified. You can now accept consultation requests.',
            'type' => 'success',
            'is_read' => false,
        ]);

        return back()->with('success', 'Veterinarian account approved successfully!');
    }

    public function reject(Request $request, User $vet)
    {
        if (!$vet->isVet()) {
            abort(404);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $vet->update(['status' => 'rejected']);

        VetDocument::where('user_id', $vet->id)->update([
            'status' => 'rejected',
            'admin_notes' => $request->reason,
        ]);

        AppNotification::create([
            'user_id' => $vet->id,
            'title' => 'Verification Update',
            'message' => 'Your application was not approved at this time. Reason: ' . $request->reason,
            'type' => 'warning',
            'is_read' => false,
        ]);

        return back()->with('info', 'Veterinarian application rejected.');
    }

    public function suspend(User $vet)
    {
        $vet->update(['status' => 'suspended']);
        if ($vet->vetProfile) {
            $vet->vetProfile->update(['is_available' => false]);
        }
        return back()->with('success', 'User account suspended.');
    }

    public function reactivate(User $vet)
    {
        $vet->update(['status' => 'active']);
        return back()->with('success', 'User account reactivated.');
    }

    public function updateFees(Request $request, User $vet)
    {
        if (!$vet->isVet()) {
            abort(404);
        }

        $request->validate([
            'consultation_fee' => 'required|numeric|min:0',
            'additional_pet_fee' => 'required|numeric|min:0',
            'additional_pet_duration' => 'required|integer|min:1|max:120',
        ]);

        if ($vet->vetProfile) {
            $vet->vetProfile->update([
                'consultation_fee' => $request->consultation_fee,
                'additional_pet_fee' => $request->additional_pet_fee,
                'additional_pet_duration' => $request->additional_pet_duration,
            ]);
        }

        return back()->with('success', "Updated consultation rates and duration for Dr. {$vet->name}.");
    }
}
