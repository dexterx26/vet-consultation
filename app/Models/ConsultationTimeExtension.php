<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationTimeExtension extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'requested_by',
        'minutes',
        'credits_cost',
        'status',
        'reviewed_by',
        'reviewed_at',
        'decline_reason',
    ];

    protected $casts = [
        'minutes' => 'integer',
        'credits_cost' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Get available extension packages (admin-configurable).
     *
     * @return array<array{minutes: int, credits: int}>
     */
    public static function getPackages(): array
    {
        $setting = SystemSetting::get('time_extension_packages');
        if ($setting) {
            $decoded = is_array($setting) ? $setting : json_decode($setting, true);
            if (is_array($decoded) && count($decoded) > 0) {
                usort($decoded, fn($a, $b) => ((int)$a['minutes']) <=> ((int)$b['minutes']));
                return array_values(array_map(fn($item) => [
                    'minutes' => (int) ($item['minutes'] ?? 10),
                    'credits' => (int) ($item['credits'] ?? 50),
                ], $decoded));
            }
        }

        return [
            ['minutes' => 10, 'credits' => 50],
            ['minutes' => 15, 'credits' => 75],
            ['minutes' => 20, 'credits' => 100],
            ['minutes' => 25, 'credits' => 125],
            ['minutes' => 30, 'credits' => 150],
        ];
    }

    /**
     * Get credits cost for a specific duration (in minutes).
     */
    public static function getCostForMinutes(int $minutes): ?int
    {
        $packages = static::getPackages();
        foreach ($packages as $pkg) {
            if ((int)$pkg['minutes'] === $minutes) {
                return (int)$pkg['credits'];
            }
        }

        // Fallback: 5 credits per minute
        return $minutes * 5;
    }
}
