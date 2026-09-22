<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsultationTimeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $consultationId;
    public string $action; // 'requested', 'approved', 'declined', 'cancelled', 'free_time_added'
    public ?array $extension;
    public ?array $timerData;
    public ?int $clientCredits;

    public function __construct(
        int $consultationId,
        string $action,
        ?array $extension = null,
        ?array $timerData = null,
        ?int $clientCredits = null
    ) {
        $this->consultationId = $consultationId;
        $this->action = $action;
        $this->extension = $extension;
        $this->timerData = $timerData;
        $this->clientCredits = $clientCredits;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('consultation.' . $this->consultationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'time.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'consultation_id' => $this->consultationId,
            'action' => $this->action,
            'extension' => $this->extension,
            'timer' => $this->timerData,
            'client_credits' => $this->clientCredits,
        ];
    }
}
