<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TelemedicineMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public string $sessionUuid;
    public array $message;

    /**
     * Create a new event instance.
     */
    public function __construct(string $sessionUuid, array $message)
    {
        $this->sessionUuid = $sessionUuid;
        $this->message = $message;
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
            'event' => 'message.sent',
            'message' => $this->message,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
