<?php

namespace App\Jobs;

class ProcessKonsinyasiEvent
{
    public function __construct(
        public array $payload,
        public string $messageId,
        public string $routingKey = ''
    ) {}
}