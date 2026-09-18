<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnimalType;
use App\Models\Breed;
use Illuminate\Http\Request;

class PetCategoryController extends Controller
{
    public function index()
    {
        $animalTypes = AnimalType::with('breeds')->withCount('pets')->get();
        return view('admin.categories.index', compact('animalTypes'));
    }

    public function storeType(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:animal_types|max:255',
            'icon' => 'nullable|string|max:100',
        ]);

        AnimalType::create([
            'name' => $request->name,
            'icon' => $request->icon ?: 'fa-paw',
            'is_active' => true,
        ]);

        return back()->with('success', 'Animal category added successfully.');
    }

    public function storeBreed(Request $request)
    {
        $request->validate([
            'animal_type_id' => 'required|exists:animal_types,id',
            'name' => 'required|string|max:255',
        ]);

        Breed::create([
            'animal_type_id' => $request->animal_type_id,
            'name' => $request->name,
        ]);

        return back()->with('success', 'Breed added successfully.');
    }

    public function deleteBreed(Breed $breed)
    {
        $breed->delete();
        return back()->with('success', 'Breed removed.');
    }
}
