<?php

namespace App\Events;

use App\Models\Consultation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsultationDeclined implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Consultation $consultation;

    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
        $this->consultation->loadMissing(['vet', 'client', 'pet', 'pets']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('consultation.' . $this->consultation->id),
            new PrivateChannel('App.Models.User.' . $this->consultation->client_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'consultation.declined';
    }

    public function broadcastWith(): array
    {
        $consult = $this->consultation;
        return [
            'id' => $consult->id,
            'consultation_id' => $consult->id,
            'consultation_number' => $consult->consultation_number,
            'status' => 'declined',
            'status_label' => 'Declined',
            'decline_reason' => $consult->decline_reason,
            'vet_name' => $consult->vet ? $consult->vet->name : 'Doctor',
            'client_id' => $consult->client_id,
            'pet_names' => $consult->all_pets->pluck('name')->join(', ') ?: ($consult->pet->name ?? 'Pet'),
            'updated_at' => now()->toISOString(),
            'fetch_url' => route('client.bookings.status', $consult),
        ];
    }
}
