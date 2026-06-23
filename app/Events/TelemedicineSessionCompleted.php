<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TelemedicineSessionCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public string $sessionUuid;

    /**
     * Create a new event instance.
     */
    public function __construct(string $sessionUuid)
    {
        $this->sessionUuid = $sessionUuid;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('session.' . $this->sessionUuid),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'event' => 'session.completed',
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'session.completed';
    }
}
