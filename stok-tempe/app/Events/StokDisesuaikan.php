<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StokDisesuaikan implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $payload,
        public string $messageId
    ) {}

    public function broadcastOn(): array
    {
        return ['stok-penerima-queue'];
    }

    public function broadcastAs(): string
    {
        return 'StokDisesuaikan';
    }
}