<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function booted()
    {
        static::saved(function ($user) {
            if ($user->wasChanged('credits') || !Cache::has("user_{$user->id}_credits")) {
                Cache::put("user_{$user->id}_credits", (int) ($user->credits ?? 0), 3600);
            }
        });

        static::deleted(function ($user) {
            Cache::forget("user_{$user->id}_credits");
        });
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'status',
        'credits',
        'profile_photo',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'credits' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isVet(): bool
    {
        return $this->role === 'veterinarian';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isVetApproved(): bool
    {
        return $this->isVet() && $this->status === 'active';
    }

    public function clientProfile()
    {
        return $this->hasOne(ClientProfile::class, 'user_id');
    }

    public function vetProfile()
    {
        return $this->hasOne(VetProfile::class, 'user_id');
    }

    public function vetDocuments()
    {
        return $this->hasMany(VetDocument::class, 'user_id');
    }

    public function pets()
    {
        return $this->hasMany(Pet::class, 'user_id');
    }

    public function vetAvailabilities()
    {
        return $this->hasMany(VetAvailability::class, 'user_id');
    }

    public function clientConsultations()
    {
        return $this->hasMany(Consultation::class, 'client_id');
    }

    public function vetConsultations()
    {
        return $this->hasMany(Consultation::class, 'vet_id');
    }

    public function appNotifications()
    {
        return $this->hasMany(AppNotification::class, 'user_id');
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class, 'user_id');
    }

    public function hasSufficientCredits(int $cost): bool
    {
        return ($this->credits ?? 0) >= $cost;
    }

    public function addCredits(int $amount, ?string $notes = null): CreditTransaction
    {
        $this->credits = ($this->credits ?? 0) + $amount;
        $this->save();

        return CreditTransaction::create([
            'user_id' => $this->id,
            'consultation_id' => null,
            'amount' => $amount,
            'type' => 'admin_topup',
            'balance_after' => $this->credits,
            'notes' => $notes ?: 'Credits added by Admin',
        ]);
    }

    public function deductCredits(int $amount, ?int $consultationId = null, ?string $notes = null): ?CreditTransaction
    {
        if (!$this->hasSufficientCredits($amount)) {
            return null;
        }

        $this->credits = ($this->credits ?? 0) - $amount;
        $this->save();

        return CreditTransaction::create([
            'user_id' => $this->id,
            'consultation_id' => $consultationId,
            'amount' => -$amount,
            'type' => 'booking_deduction',
            'balance_after' => $this->credits,
            'notes' => $notes ?: 'Booking confirmation credit deduction',
        ]);
    }
}
