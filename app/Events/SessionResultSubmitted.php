<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionResultSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $sessionCode,
        public readonly array  $result,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('session.' . $this->sessionCode)];
    }

    public function broadcastAs(): string
    {
        return 'result.submitted';
    }

    public function broadcastWith(): array
    {
        return ['result' => $this->result];
    }
}
