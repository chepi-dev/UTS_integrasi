<?php

namespace App\Messaging;

use App\Jobs\ProcessKonsinyasiEvent;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitPublisher
{
    public function publish(string $routingKey, array|string $payload, string $messageId, string $jenis): void
    {
        $payloadArray = is_string($payload) ? json_decode($payload, true) : $payload;

        $connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST', 'rabbitmq'),
            env('RABBITMQ_PORT', 5672),
            env('RABBITMQ_USER', 'sail'),
            env('RABBITMQ_PASSWORD', 'password'),
            env('RABBITMQ_VHOST', '/')
        );

        $channel = $connection->channel();

        // Pastikan exchange topic 'toko.events' dan queue tujuan tersedia
        $channel->exchange_declare('toko.events', 'topic', false, true, false);
        $channel->queue_declare('stok-penerima-queue', false, true, false, false);
        $channel->queue_bind('stok-penerima-queue', 'toko.events', 'konsinyasi.*');

        $job = new ProcessKonsinyasiEvent($payloadArray, $messageId, $routingKey);

        $body = json_encode([
            'uuid' => $messageId,
            'displayName' => ProcessKonsinyasiEvent::class,
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'maxTries' => 3,
            'delay' => null,
            'timeout' => null,
            'timeoutAt' => null,
            'data' => [
                'commandName' => ProcessKonsinyasiEvent::class,
                'command' => serialize($job),
            ],
        ]);

        $msg = new AMQPMessage($body, [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'message_id' => $messageId,
        ]);

        $channel->basic_publish($msg, 'toko.events', $routingKey);

        $channel->close();
        $connection->close();
    }
}