<?php
// app/Events/MessageSent.php
namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // Private channel for both users
        return new PrivateChannel('chat.' . min($this->message->senderId, $this->message->receiverId) . '.' . max($this->message->senderId, $this->message->receiverId));
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'senderId' => $this->message->senderId,
            'receiverId' => $this->message->receiverId,
            'message' => $this->message->message,
            'timestamp' => $this->message->created_at->toISOString(),
            'isRead' => $this->message->isRead,
            'sender' => [
                'id' => $this->message->sender->id,
                'firstName' => $this->message->sender->firstName,
                'lastName' => $this->message->sender->lastName,
                'avatar' => $this->message->sender->profileImage
            ]
        ];
    }
}