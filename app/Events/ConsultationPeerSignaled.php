<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsultationPeerSignaled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $consultationId;
    public string $peerId;
    public string $role; // 'vet' or 'client'

    public function __construct(int $consultationId, string $peerId, string $role)
    {
        $this->consultationId = $consultationId;
        $this->peerId = $peerId;
        $this->role = $role;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('consultation.' . $this->consultationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'peer.signaled';
    }

    public function broadcastWith(): array
    {
        return [
            'consultation_id' => $this->consultationId,
            'peer_id' => $this->peerId,
            'role' => $this->role,
        ];
    }
}
