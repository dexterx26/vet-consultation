<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_number',
        'client_id',
        'vet_id',
        'pet_id',
        'type',
        'status',
        'scheduled_at',
        'suggested_scheduled_at',
        'reschedule_note',
        'fee',
        'duration_minutes',
        'base_fee',
        'additional_fee',
        'credits_cost',
        'credits_deducted',
        'reason',
        'attachments',
        'decline_reason',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'suggested_scheduled_at' => 'datetime',
        'attachments' => 'array',
        'fee' => 'decimal:2',
        'base_fee' => 'decimal:2',
        'additional_fee' => 'decimal:2',
        'duration_minutes' => 'integer',
        'credits_cost' => 'integer',
        'credits_deducted' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function vet()
    {
        return $this->belongsTo(User::class, 'vet_id');
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class, 'pet_id');
    }

    public function pets()
    {
        return $this->belongsToMany(Pet::class, 'consultation_pets')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function getAllPetsAttribute()
    {
        if ($this->relationLoaded('pets') && $this->pets->isNotEmpty()) {
            return $this->pets;
        }
        $pets = $this->pets()->get();
        if ($pets->isNotEmpty()) {
            return $pets;
        }
        return $this->pet ? collect([$this->pet]) : collect();
    }

    public function messages()
    {
        return $this->hasMany(ConsultationMessage::class, 'consultation_id');
    }

    public function record()
    {
        return $this->hasOne(ConsultationRecord::class, 'consultation_id');
    }

    public function call()
    {
        return $this->hasOne(ConsultationCall::class, 'consultation_id');
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'consultation_id');
    }
}
