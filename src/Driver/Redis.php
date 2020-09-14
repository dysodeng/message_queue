<?php
namespace Dy\MessageQueue\Driver;

use Closure;

/**
 * 消息队列 Redis驱动实现
 * @package Dy\MessageQueue\Driver
 */
class Redis implements DriverInterface
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * @var \Redis
     */
    private $conn;

    /**
     * Redis constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->connection();
    }

    public function connection()
    {
        $this->conn = new \Redis();
        $this->conn->connect(
            $this->config['host'] ?? '127.0.0.1',
            $this->config['port'] ?? 6379
        );
        $password = $this->config['password'] ?? '';
        if ($password) {
            $this->conn->auth($password);
        }
        $this->conn->select($this->config['database'] ?? 0);
    }

    /**
     * 队列消息
     * @param string $exchangeName      交换机名称
     * @param string $queueName         队列名称
     * @param string $routeKey          路由key
     * @param string $message           队列消息
     * @return bool
     */
    public function queue(string $exchangeName, string $queueName, string $routeKey, string $message): bool
    {
        // TODO: Implement queue() method.
        return true;
    }

    /**
     * 延时队列消息
     * @param string $exchangeName      交换机名称
     * @param string $queueName         队列名称
     * @param string $routeKey          路由key
     * @param string $message           队列消息
     * @param int $ttl                  消息生存时间(秒)
     * @return bool
     */
    public function delayQueue(string $exchangeName, string $queueName, string $routeKey, string $message, int $ttl): bool
    {
        // TODO: Implement delayQueue() method.
        return true;
    }

    /**
     * 队列消费者
     * @param Closure $consumer         消费者处理器
     * @param string $exchangeName      交换机名称
     * @param string $queueName         队列名称
     * @param string $routeKey          路由key
     */
    public function consumer(Closure $consumer, string $exchangeName, string $queueName, string $routeKey)
    {
        // TODO: Implement consumer() method.
    }

    public function __destruct()
    {

    }
}
