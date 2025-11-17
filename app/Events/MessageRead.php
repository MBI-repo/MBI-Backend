<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public int $conversationId;
    public int $messageId;
    public int $readerId;
    public string $readAt;

    public function __construct(int $conversationId, int $messageId, int $readerId, string $readAt)
    {
        $this->conversationId = $conversationId;
        $this->messageId = $messageId;
        $this->readerId = $readerId;
        $this->readAt = $readAt;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('conversation.' . $this->conversationId);
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'message.read',
            'messageId' => $this->messageId,
            'readerId' => $this->readerId,
            'readAt' => $this->readAt,
        ];
    }
}