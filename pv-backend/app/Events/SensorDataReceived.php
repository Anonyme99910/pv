<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SensorDataReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;
    public string $panelCode;

    public function __construct(string $panelCode, array $data)
    {
        $this->panelCode = $panelCode;
        $this->data = $data;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('sensors'),
            new Channel('panel.' . $this->panelCode),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sensor.data';
    }

    public function broadcastWith(): array
    {
        return [
            'panel_code' => $this->panelCode,
            'data' => $this->data,
            'timestamp' => now()->toISOString(),
        ];
    }
}
