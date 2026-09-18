<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'symptoms',
        'assessment',
        'recommendations',
        'treatment_advice',
        'medication_info',
        'follow_up_instructions',
        'additional_notes',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }
}
