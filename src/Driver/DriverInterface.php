<?php
namespace Dy\MessageQueue\Driver;

use Closure;
use Dy\MessageQueue\Message\Message;
use Monolog\Logger;

/**
 * 消息队列驱动接口
 * @package Dy\MessageQueue\Driver
 */
interface DriverInterface
{
    /**
     * DriverInterface constructor.
     * @param array $config
     */
    public function __construct(array $config = []);

    /**
     * 设置日志处理器
     * @param Logger $logger
     * @return mixed
     */
    public function setLogger(Logger $logger);

    /**
     * 发送普通队列消息
     * @param string $exchangeName  交换机名称
     * @param string $queueName     队列名称
     * @param string $routeKey      路由key
     * @param string $message       队列消息
     * @return Message
     */
    public function queue(string $exchangeName, string $queueName, string $routeKey, string $message): Message;

    /**
     * 发送延时队列消息
     * @param string $exchangeName  交换机名称
     * @param string $queueName     队列名称
     * @param string $routeKey      路由key
     * @param string $message       队列消息
     * @param int $ttl              消息延时时间(秒)
     * @return Message
     */
    public function delayQueue(string $exchangeName, string $queueName, string $routeKey, string $message, int $ttl): Message;

    /**
     * 队列消费者
     * @param Closure $consumer     消费者处理器
     * @param string $exchangeName  交换机名称
     * @param string $queueName     队列名称
     * @param string $routeKey      路由key
     * @param bool $is_delay        是否延时任务
     * @return mixed
     */
    public function consumer(Closure $consumer, string $exchangeName, string $queueName, string $routeKey, bool $is_delay = false);
}
