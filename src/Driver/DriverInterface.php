<?php
namespace Dy\MessageQueue\Driver;

use Closure;
use Dy\MessageQueue\Message\Message;

interface DriverInterface
{
    public function __construct(array $config = []);

    public function queue(string $exchangeName, string $queueName, string $routeKey, string $message): Message;

    public function delayQueue(string $exchangeName, string $queueName, string $routeKey, string $message, int $ttl): Message;

    public function consumer(Closure $consumer, string $exchangeName, string $queueName, string $routeKey, bool $is_delay = false);
}
