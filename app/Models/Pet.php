<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'animal_type_id',
        'breed_id',
        'custom_breed',
        'sex',
        'dob',
        'age_text',
        'weight',
        'color',
        'photo',
        'medical_notes',
        'existing_conditions',
        'allergies',
        'current_medications',
        'vaccination_info',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function animalType()
    {
        return $this->belongsTo(AnimalType::class, 'animal_type_id');
    }

    public function breed()
    {
        return $this->belongsTo(Breed::class, 'breed_id');
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class, 'pet_id');
    }

    public function getBreedNameAttribute()
    {
        if ($this->breed) {
            return $this->breed->name;
        }
        return $this->custom_breed ?: 'Mixed / Unknown';
    }
}
