<?php

namespace App\Events;

use App\Models\ChMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(ChMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('consultation.' . $this->message->consultation_id);
    }

    public function broadcastWith()
    {
        $isSender = $this->message->from_id === auth()->guard('api')->id() &&
                    $this->message->from_type === get_class(auth()->guard('api')->user());

        return [
            'message' => [
                'id' => $this->message->id,
                'body' => $this->message->body,
                'attachment' => $this->message->attachment,
                'sender_id' => $this->message->from_id,
                'sender_type' => $this->message->from_type,
                'is_sender' => $isSender,
                'seen' => $this->message->seen,
                'created_at' => $this->message->created_at->toISOString(),
                'time_ago' => $this->message->created_at->diffForHumans(),
                'time' => $this->message->created_at->format('H:i')
            ]
        ];
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }
}
