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
    public string $messageUuid;
    public string $readerUuid;
    public string $readAt;

    public function __construct(int $conversationId, string $messageUuid, string $readerUuid, string $readAt)
    {
        $this->conversationId = $conversationId;
        $this->messageUuid = $messageUuid;
        $this->readerUuid = $readerUuid;
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
            'messageUuid' => $this->messageUuid,
            'readerUuid' => $this->readerUuid,
            'readAt' => $this->readAt,
        ];
    }
}