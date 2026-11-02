<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessStokDisesuaikanEvent implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $payload,
        public string $messageId
    ) {}

    public function handle(): void
    {
        // Job ini hanya digunakan publisher untuk mempublikasikan struktur data ke RabbitMQ
    }
}