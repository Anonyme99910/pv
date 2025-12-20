<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $alert;

    public function __construct(array $alert)
    {
        $this->alert = $alert;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('alerts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'alert.created';
    }

    public function broadcastWith(): array
    {
        return [
            'alert' => $this->alert,
            'timestamp' => now()->toISOString(),
        ];
    }
}
