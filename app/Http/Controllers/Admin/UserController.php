<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->get('role', 'client');
        $users = User::where('role', $role)->with(['clientProfile', 'pets'])->paginate(15);

        return view('admin.users.index', compact('users', 'role'));
    }

    public function addCredits(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|integer|min:1|max:50000',
            'notes' => 'nullable|string|max:255',
        ]);

        $user->addCredits($request->amount, $request->notes ?: 'Top-up added by administrator');

        return back()->with('success', "Added {$request->amount} credits to {$user->name}. New balance: {$user->credits} credits.");
    }
}
