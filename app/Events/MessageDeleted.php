<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $consultationId;
    public $messageId;
    public $deletedAt;

    /**
     * Create a new event instance.
     */
    public function __construct($consultationId, $messageId)
    {
        $this->consultationId = $consultationId;
        $this->messageId = $messageId;
        $this->deletedAt = now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new PrivateChannel('consultation.' . $this->consultationId);
    }

    public function broadcastAs(): string
    {
        return 'message-deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'consultation_id' => $this->consultationId,
            'message_id' => $this->messageId,
            'deleted_at' => $this->deletedAt,
            'user_id' => auth()->guard('api')->id() ?? null
        ];
    }
}
