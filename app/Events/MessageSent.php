<?php

namespace App\Events;

use App\Models\ChMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow 
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // Pastikan ID konsultasi valid
        return new PrivateChannel('consultation.' . $this->message->consultation_id);
    }

    public function broadcastWith()
    {
        // Kita kirim data mentah saja, biarkan Frontend yang menentukan is_sender
        // agar lebih aman dan tidak error saat antrian.
        return [
            'message' => [
                'id' => $this->message->id,
                'body' => $this->message->body,
                'attachment' => $this->message->attachment,
                'sender_id' => $this->message->from_id, // Frontend bandingkan ini dengan User ID login
                'created_at' => $this->message->created_at,
                'time' => $this->message->created_at->format('H:i'),
                // Tambahkan data user pengirim agar avatar muncul realtime
                'sender_avatar' => $this->message->user ? asset('storage/'.$this->message->user->avatar) : null,
                'sender_name' => $this->message->user ? $this->message->user->name : 'User',
            ]
        ];
    }

    public function broadcastAs()
    {
        // Nama event yang akan didengar Frontend
        return 'message.sent';
    }
}
