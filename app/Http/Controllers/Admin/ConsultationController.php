<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $query = Consultation::with(['client', 'vet', 'pet', 'record']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $consultations = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.consultations.index', compact('consultations', 'status'));
    }

    public function show(Consultation $consultation)
    {
        $consultation->load(['client', 'vet', 'pet', 'messages.sender', 'record', 'call']);
        return view('admin.consultations.show', compact('consultation'));
    }
}
