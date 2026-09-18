<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnimalType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function breeds()
    {
        return $this->hasMany(Breed::class, 'animal_type_id');
    }

    public function pets()
    {
        return $this->hasMany(Pet::class, 'animal_type_id');
    }
}
