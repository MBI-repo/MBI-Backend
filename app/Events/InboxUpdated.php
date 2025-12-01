<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class InboxUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public int $userId;
    public string $userUuid;
    public array $item;

    public function __construct(int $userId, string $userUuid, array $item)
    {
        $this->userId = $userId;
        $this->userUuid = $userUuid;
        $this->item = $item;
    }

    public function oldbroadcastOn(): array
    {
        return [
            new PrivateChannel('inbox.' . $this->userId),
            new PrivateChannel('inbox.' . $this->userUuid),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            // channel the open-chat tab listens on
            new PrivateChannel('conversation.' . $this->item['conversationUuid']),

            // channel the conversation-list tab listens on
            new PrivateChannel('inbox.' . $this->userUuid),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'inbox.updated',
            'item' => $this->item,
        ];
    }

    public function broadcastAs(): string
    {
        return 'inbox.updated';
    }
}
