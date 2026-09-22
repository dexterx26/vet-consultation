<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsultationMessageRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $consultationId;
    public int $readerId;
    public string $readAt;

    public function __construct(int $consultationId, int $readerId, string $readAt)
    {
        $this->consultationId = $consultationId;
        $this->readerId = $readerId;
        $this->readAt = $readAt;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('consultation.' . $this->consultationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'consultation_id' => $this->consultationId,
            'reader_id' => $this->readerId,
            'read_at' => $this->readAt,
        ];
    }
}
