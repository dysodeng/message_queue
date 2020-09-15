<?php
namespace Dy\MessageQueue\Driver;

use Closure;

interface DriverInterface
{
    public function __construct(array $config = []);

    public function queue(string $exchangeName, string $queueName, string $routeKey, string $message): bool;

    public function delayQueue(string $exchangeName, string $queueName, string $routeKey, string $message, int $ttl): bool;

    public function consumer(Closure $consumer, string $exchangeName, string $queueName, string $routeKey, bool $is_delay = false);
}
