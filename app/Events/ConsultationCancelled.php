<?php

namespace App\Events;

use App\Models\Consultation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsultationCancelled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Consultation $consultation;

    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
        $this->consultation->loadMissing(['client', 'pet', 'pets']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('vet.' . $this->consultation->vet_id),
            new PrivateChannel('consultation.' . $this->consultation->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'consultation.cancelled';
    }

    public function broadcastWith(): array
    {
        $consult = $this->consultation;
        $petNames = $consult->all_pets->pluck('name')->join(', ') ?: ($consult->pet->name ?? 'Patient');

        return [
            'id' => $consult->id,
            'consultation_id' => $consult->id,
            'consultation_number' => $consult->consultation_number,
            'status' => 'cancelled_by_client',
            'client_name' => $consult->client ? $consult->client->name : 'Client',
            'pet_names' => $petNames,
            'vet_id' => $consult->vet_id,
            'cancelled_at' => now()->toISOString(),
        ];
    }
}
