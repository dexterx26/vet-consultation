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
        'is_follow_up',
        'parent_consultation_id',
        'reason',
        'attachments',
        'decline_reason',
        'time_consumed_seconds',
        'doctor_joined_at',
        'doctor_last_seen_at',
        'client_joined_at',
        'client_last_seen_at',
        'last_deducted_at',
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
        'is_follow_up' => 'boolean',
        'time_consumed_seconds' => 'integer',
        'doctor_joined_at' => 'datetime',
        'doctor_last_seen_at' => 'datetime',
        'client_joined_at' => 'datetime',
        'client_last_seen_at' => 'datetime',
        'last_deducted_at' => 'datetime',
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

    public function parentConsultation()
    {
        return $this->belongsTo(Consultation::class, 'parent_consultation_id');
    }

    public function followUpConsultations()
    {
        return $this->hasMany(Consultation::class, 'parent_consultation_id');
    }

    public function call()
    {
        return $this->hasOne(ConsultationCall::class, 'consultation_id');
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'consultation_id');
    }

    public function timeExtensions()
    {
        return $this->hasMany(ConsultationTimeExtension::class, 'consultation_id');
    }

    public function pendingTimeExtension()
    {
        return $this->hasOne(ConsultationTimeExtension::class, 'consultation_id')
            ->where('status', 'pending')
            ->latest();
    }

    public function getTotalDurationSecondsAttribute(): int
    {
        return ($this->duration_minutes ?: 15) * 60;
    }

    public function getRemainingSecondsAttribute(): int
    {
        return max(0, $this->total_duration_seconds - ($this->time_consumed_seconds ?? 0));
    }

    public function getFormattedRemainingTimeAttribute(): string
    {
        $totalSecs = $this->remaining_seconds;
        $mins = floor($totalSecs / 60);
        $secs = $totalSecs % 60;
        return sprintf('%02d:%02d', $mins, $secs);
    }

    public function getFormattedConsumedTimeAttribute(): string
    {
        $totalSecs = $this->time_consumed_seconds ?? 0;
        $mins = floor($totalSecs / 60);
        $secs = $totalSecs % 60;
        return sprintf('%02d:%02d', $mins, $secs);
    }

    public function isDoctorPresent(): bool
    {
        return $this->doctor_last_seen_at !== null && abs((int) now()->diffInSeconds($this->doctor_last_seen_at)) <= 8;
    }

    public function isClientPresent(): bool
    {
        return $this->client_last_seen_at !== null && abs((int) now()->diffInSeconds($this->client_last_seen_at)) <= 8;
    }

    public function isTimerRunning(): bool
    {
        return $this->isDoctorPresent()
            && $this->remaining_seconds > 0
            && !in_array($this->status, ['completed', 'declined', 'cancelled_by_client', 'cancelled_by_vet', 'expired']);
    }

    public function isExpired(): bool
    {
        return $this->remaining_seconds <= 0;
    }
}
