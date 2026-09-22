<?php

namespace App\Events;

use App\Models\ConsultationMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsultationMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ConsultationMessage $message;

    public function __construct(ConsultationMessage $message)
    {
        $this->message = $message;
        $this->message->loadMissing('sender');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('consultation.' . $this->message->consultation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'consultation_id' => $this->message->consultation_id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender ? $this->message->sender->name : 'User',
            'message' => $this->message->message,
            'attachment_url' => $this->message->attachment_path ? asset('storage/' . $this->message->attachment_path) : null,
            'attachment_type' => $this->message->attachment_type,
            'created_at' => $this->message->created_at->format('h:i A'),
            'is_read' => !is_null($this->message->read_at),
            'read_at' => $this->message->read_at ? $this->message->read_at->format('h:i A') : null,
        ];
    }
}
