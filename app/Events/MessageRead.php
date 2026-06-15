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
    public string $conversationUuid;
    public string $messageUuid;
    public string $readerUuid;
    public string $readAt;

    public function __construct(int $conversationId, string $conversationUuid, string $messageUuid, string $readerUuid, string $readAt)
    {
        $this->conversationId = $conversationId;
        $this->conversationUuid = $conversationUuid;
        $this->messageUuid = $messageUuid;
        $this->readerUuid = $readerUuid;
        $this->readAt = $readAt;
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
            'event' => 'message.read',
            'messageUuid' => $this->messageUuid,
            'readerUuid' => $this->readerUuid,
            'readAt' => $this->readAt,
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }
}