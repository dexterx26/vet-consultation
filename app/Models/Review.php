<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'client_id',
        'vet_id',
        'rating',
        'comment',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function vet()
    {
        return $this->belongsTo(User::class, 'vet_id');
    }
}
