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
    public string $messageUuid;

    public function __construct(int $conversationId, string $messageUuid)
    {
        $this->conversationId = $conversationId;
        $this->messageUuid = $messageUuid;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('conversation.' . $this->conversationId);
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'message.deleted',
            'messageUuid' => $this->messageUuid,
        ];
    }
}