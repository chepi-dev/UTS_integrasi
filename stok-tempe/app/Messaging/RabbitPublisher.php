<?php

namespace App\Messaging;

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

        $body = json_encode([
            'displayName' => 'App\\Jobs\\ProcessStokDisesuaikanEvent',
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'maxTries' => null,
            'delay' => null,
            'timeout' => null,
            'timeoutAt' => null,
            'data' => [
                'commandName' => 'App\\Jobs\\ProcessStokDisesuaikanEvent',
                'command' => serialize(new \App\Jobs\ProcessStokDisesuaikanEvent($payloadArray, $messageId))
            ]
        ]);

        $msg = new AMQPMessage($body, [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'message_id' => $messageId,
        ]);

        $channel->basic_publish($msg, '', 'stok-penerima-queue');

        $channel->close();
        $connection->close();
    }
}