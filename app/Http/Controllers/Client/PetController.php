<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\AnimalType;
use App\Models\Breed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PetController extends Controller
{
    public function index()
    {
        $pets = Auth::user()->pets()->with(['animalType', 'breed', 'consultations'])->get();
        return view('client.pets.index', compact('pets'));
    }

    public function create()
    {
        $animalTypes = AnimalType::where('is_active', true)->with('breeds')->get();
        return view('client.pets.create', compact('animalTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'animal_type_id' => 'required|exists:animal_types,id',
            'breed_id' => 'nullable|exists:breeds,id',
            'custom_breed' => 'nullable|string|max:255',
            'sex' => 'required|in:Male,Female',
            'dob' => 'nullable|date',
            'age_text' => 'nullable|string|max:100',
            'weight' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:100',
            'photo' => 'nullable|image|max:2048',
            'medical_notes' => 'nullable|string',
            'existing_conditions' => 'nullable|string',
            'allergies' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'vaccination_info' => 'nullable|string',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('pets', 'public');
        }

        Pet::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'animal_type_id' => $request->animal_type_id,
            'breed_id' => $request->breed_id,
            'custom_breed' => $request->custom_breed,
            'sex' => $request->sex,
            'dob' => $request->dob,
            'age_text' => $request->age_text,
            'weight' => $request->weight,
            'color' => $request->color,
            'photo' => $photoPath,
            'medical_notes' => $request->medical_notes,
            'existing_conditions' => $request->existing_conditions,
            'allergies' => $request->allergies,
            'current_medications' => $request->current_medications,
            'vaccination_info' => $request->vaccination_info,
        ]);

        return redirect()->route('client.pets.index')->with('success', 'Pet profile created successfully!');
    }

    public function show(Pet $pet)
    {
        $this->authorizePetOwner($pet);
        $pet->load(['animalType', 'breed', 'consultations.vet', 'consultations.record']);
        return view('client.pets.show', compact('pet'));
    }

    public function edit(Pet $pet)
    {
        $this->authorizePetOwner($pet);
        $animalTypes = AnimalType::where('is_active', true)->with('breeds')->get();
        return view('client.pets.edit', compact('pet', 'animalTypes'));
    }

    public function update(Request $request, Pet $pet)
    {
        $this->authorizePetOwner($pet);

        $request->validate([
            'name' => 'required|string|max:255',
            'animal_type_id' => 'required|exists:animal_types,id',
            'breed_id' => 'nullable|exists:breeds,id',
            'custom_breed' => 'nullable|string|max:255',
            'sex' => 'required|in:Male,Female',
            'dob' => 'nullable|date',
            'age_text' => 'nullable|string|max:100',
            'weight' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:100',
            'photo' => 'nullable|image|max:2048',
            'medical_notes' => 'nullable|string',
            'existing_conditions' => 'nullable|string',
            'allergies' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'vaccination_info' => 'nullable|string',
        ]);

        if ($request->hasFile('photo')) {
            $pet->photo = $request->file('photo')->store('pets', 'public');
        }

        $pet->update($request->only([
            'name', 'animal_type_id', 'breed_id', 'custom_breed', 'sex', 'dob',
            'age_text', 'weight', 'color', 'medical_notes', 'existing_conditions',
            'allergies', 'current_medications', 'vaccination_info'
        ]));

        return redirect()->route('client.pets.index')->with('success', 'Pet details updated successfully!');
    }

    public function destroy(Pet $pet)
    {
        $this->authorizePetOwner($pet);
        $pet->delete();
        return redirect()->route('client.pets.index')->with('success', 'Pet profile deleted.');
    }

    private function authorizePetOwner(Pet $pet)
    {
        if ($pet->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
