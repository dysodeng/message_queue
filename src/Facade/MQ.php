<?php

namespace Dy\MessageQueue\Facade;

use Dy\MessageQueue\Message\Message;
use Dy\MessageQueue\MessageQueue;
use Closure;
use Illuminate\Support\Facades\Facade;

/**
 * MQ Facade
 * @package Dy\MessageQueue\Facade
 *
 * @method static Message queue($exchangeName, $queueName, $routeKey, $message = '')
 * @method static Message delayQueue(string $exchangeName, string $queueName, string $routeKey, string $message, int $ttl)
 * @method static consumer(Closure $consumer, string $exchangeName, string $queueName, string $routeKey)
 * @method static delayConsumer(Closure $consumer, string $exchangeName, string $queueName, string $routeKey)
 *
 * @see MessageQueue
 */
class MQ extends Facade
{
    /**
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return MessageQueue::class;
    }
}
