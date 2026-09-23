<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('consultation.{consultationId}', function ($user, $consultationId) {
    if ($user->isAdmin()) {
        return true;
    }
    $consultation = \App\Models\Consultation::find($consultationId);
    if (!$consultation) {
        return false;
    }
    return (int) $consultation->client_id === (int) $user->id 
        || (int) $consultation->vet_id === (int) $user->id;
});

Broadcast::channel('vet.{vetId}', function ($user, $vetId) {
    return (int) $user->id === (int) $vetId && $user->isVet();
});

