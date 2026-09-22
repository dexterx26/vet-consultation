<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;
use Illuminate\Support\Str;

class Pet extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::saving(function ($pet) {
            if ($pet->dob) {
                $pet->attributes['age_text'] = static::calculateAgeText($pet->dob);
            }
        });
    }

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

    public function consultationSessions()
    {
        return $this->belongsToMany(Consultation::class, 'consultation_pets')->withPivot('is_primary')->withTimestamps();
    }

    public function getBreedNameAttribute()
    {
        if ($this->breed) {
            return $this->breed->name;
        }
        return $this->custom_breed ?: 'Mixed / Unknown';
    }

    public function getAgeTextAttribute($value)
    {
        if ($this->dob) {
            return static::calculateAgeText($this->dob);
        }
        return $value ?: 'N/A';
    }

    public static function calculateAgeText($dob): string
    {
        if (!$dob) {
            return 'N/A';
        }

        $dobCarbon = $dob instanceof Carbon ? $dob : Carbon::parse($dob);
        $now = Carbon::now();

        if ($dobCarbon->isFuture()) {
            return 'Newborn';
        }

        $diffYears = (int) $dobCarbon->diffInYears($now);
        $diffMonths = (int) ($dobCarbon->diffInMonths($now) % 12);
        $diffDays = (int) $dobCarbon->diffInDays($now);

        if ($diffYears >= 1) {
            if ($diffMonths > 0) {
                return $diffYears . ' ' . Str::plural('year', $diffYears) . ' ' . $diffMonths . ' ' . Str::plural('month', $diffMonths) . ' old';
            }
            return $diffYears . ' ' . Str::plural('year', $diffYears) . ' old';
        }

        if ($diffMonths >= 1) {
            return $diffMonths . ' ' . Str::plural('month', $diffMonths) . ' old';
        }

        $diffWeeks = (int) floor($diffDays / 7);
        if ($diffWeeks >= 1) {
            return $diffWeeks . ' ' . Str::plural('week', $diffWeeks) . ' old';
        }

        if ($diffDays > 0) {
            return $diffDays . ' ' . Str::plural('day', $diffDays) . ' old';
        }

        return 'Newborn';
    }
}
