<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public int $conversationId;
    public string $conversationUuid;
    public string $messageUuid;

    public function __construct(int $conversationId, string $conversationUuid, string $messageUuid)
    {
        $this->conversationId = $conversationId;
        $this->conversationUuid = $conversationUuid;
        $this->messageUuid = $messageUuid;
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
            'event' => 'message.deleted',
            'messageUuid' => $this->messageUuid,
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.deleted';
    }
}