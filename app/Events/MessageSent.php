<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public int $conversationId;
    public string $conversationUuid;
    public array $message;

    public function __construct(int $conversationId, string $conversationUuid, array $message)
    {
        $this->conversationId = $conversationId;
        $this->conversationUuid = $conversationUuid;
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversationId),
            new PrivateChannel('conversation.' . $this->conversationUuid),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'message.sent',
            'message' => $this->message,
        ];
    }
}