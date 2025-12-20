<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FaultDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $fault;

    public function __construct(array $fault)
    {
        $this->fault = $fault;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('faults'),
            new Channel('alerts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'fault.detected';
    }

    public function broadcastWith(): array
    {
        return [
            'fault' => $this->fault,
            'timestamp' => now()->toISOString(),
        ];
    }
}
