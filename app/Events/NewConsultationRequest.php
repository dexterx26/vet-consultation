<?php

namespace App\Events;

use App\Models\Consultation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class NewConsultationRequest implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Consultation $consultation;

    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
        $this->consultation->loadMissing(['client', 'pet', 'pet.animalType', 'pets', 'pets.animalType']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('vet.' . $this->consultation->vet_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'consultation.requested';
    }

    public function broadcastWith(): array
    {
        $consult = $this->consultation;
        $petNames = $consult->all_pets->pluck('name')->join(', ') ?: ($consult->pet->name ?? 'Patient');
        $animalIcon = ($consult->pet && $consult->pet->animalType) ? $consult->pet->animalType->icon : 'fa-paw';

        return [
            'id' => $consult->id,
            'consultation_number' => $consult->consultation_number,
            'client_id' => $consult->client_id,
            'client_name' => $consult->client ? $consult->client->name : 'Client',
            'vet_id' => $consult->vet_id,
            'pet_names' => $petNames,
            'animal_icon' => $animalIcon,
            'type' => $consult->type,
            'status' => 'pending',
            'scheduled_at_formatted' => $consult->scheduled_at ? $consult->scheduled_at->format('M d, Y @ g:i A') : 'Scheduled',
            'date' => $consult->scheduled_at ? $consult->scheduled_at->format('Y-m-d') : null,
            'time' => $consult->scheduled_at ? $consult->scheduled_at->format('g:i A') : '',
            'time_24' => $consult->scheduled_at ? $consult->scheduled_at->format('H:i') : '',
            'duration_minutes' => $consult->duration_minutes ?: 15,
            'fee' => (float) $consult->fee,
            'reason' => Str::limit($consult->reason ?? '', 100),
            'accept_url' => route('vet.requests.accept', $consult),
            'decline_url' => route('vet.requests.decline', $consult),
            'show_url' => route('vet.requests.show', $consult),
            'video_url' => route('consultation.video', $consult),
            'chat_url' => route('consultation.chat', $consult),
            'notes_url' => route('vet.records.create', $consult),
            'created_at' => now()->toISOString(),
        ];
    }
}
