<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VetProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'license_number',
        'clinic_name',
        'clinic_address',
        'years_experience',
        'expertise',
        'animals_handled',
        'consultation_fee',
        'follow_up_fee',
        'additional_pet_fee',
        'additional_pet_duration',
        'bio',
        'languages',
        'city',
        'province',
        'country',
        'latitude',
        'longitude',
        'is_available',
    ];

    protected $hidden = [
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'animals_handled' => 'array',
        'is_available' => 'boolean',
        'consultation_fee' => 'decimal:2',
        'follow_up_fee' => 'decimal:2',
        'additional_pet_fee' => 'decimal:2',
        'additional_pet_duration' => 'integer',
    ];

    public function getEffectiveAdditionalPetFeeAttribute(): float
    {
        return $this->additional_pet_fee !== null 
            ? (float) $this->additional_pet_fee 
            : (float) \App\Models\SystemSetting::get('default_additional_pet_fee', 250.00);
    }

    public function getEffectiveAdditionalPetDurationAttribute(): int
    {
        return $this->additional_pet_duration !== null 
            ? (int) $this->additional_pet_duration 
            : (int) \App\Models\SystemSetting::get('default_additional_pet_duration', 15);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'vet_id', 'user_id');
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?: 5.0;
    }
}
