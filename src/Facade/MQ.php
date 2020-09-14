<?php
namespace Dy\MessageQueue\Facade;

use Dy\MessageQueue\MessageQueue;
use Closure;
use Illuminate\Support\Facades\Facade;

/**
 * MQ门面
 * @package Dy\MessageQueue\Facade
 *
 * @method static MessageQueue queue($exchangeName, $queueName, $routeKey, $message = '')
 * @method static MessageQueue delayQueue(string $exchangeName, string $queueName, string $routeKey, string $message, int $ttl)
 * @method static MessageQueue consumer(Closure $consumer, string $exchangeName, string $queueName, string $routeKey)
 * @method static MessageQueue delayConsumer(Closure $consumer, string $exchangeName, string $queueName, string $routeKey)
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
        return 'mq';
    }
}
