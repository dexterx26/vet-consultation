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
        'follow_up_date',
        'follow_up_fee',
        'follow_up_consultation_id',
        'additional_notes',
    ];

    protected $casts = [
        'follow_up_date' => 'datetime',
        'follow_up_fee' => 'decimal:2',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }

    public function followUpConsultation()
    {
        return $this->belongsTo(Consultation::class, 'follow_up_consultation_id');
    }
}
