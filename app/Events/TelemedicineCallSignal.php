<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TelemedicineCallSignal implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public string $sessionUuid;
    public array $signal;

    /**
     * Create a new event instance.
     */
    public function __construct(string $sessionUuid, array $signal)
    {
        $this->sessionUuid = $sessionUuid;
        $this->signal = $signal;
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
            'event' => 'call.signal',
            'signal' => $this->signal,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'call.signal';
    }
}
