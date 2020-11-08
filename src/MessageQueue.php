<?php

namespace Dy\MessageQueue;

use Closure;
use Dy\MessageQueue\Driver\AMQP;
use Dy\MessageQueue\Driver\DriverInterface;
use Dy\MessageQueue\Driver\Redis;
use Dy\MessageQueue\Log\Log;
use Dy\MessageQueue\Message\Message;
use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class MessageQueue
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * MessageQueue constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        // 创建连接
        $config = $this->config['connections'][$this->config['driver']] ?? [];
        if (empty($config)) {
            throw new Exception($this->config['driver'].' driver not found.');
        }
        $config['retry'] = intval($this->config['retry'] ?? 3); // 重试次数

        // 创建队列驱动
        switch (strtolower($this->config['driver'])) {
            case 'amqp':
                $this->driver = new AMQP($config);
                break;
            case 'redis':
                $this->driver = new Redis($config);
                break;
            default:
                throw new Exception($this->config['driver'].' driver not found.');
        }

        // 日志
        $this->config['log'] = $this->config['log'] ?? ['level'=>'debug', 'file'=>storage_path('logs/dy_message_queue.log')];
        $logger = new Logger('MessageQueue');
        $stream = new StreamHandler($this->config['log']['file'], Log::getLevel($this->config['log']['level']));
        $stream->setFormatter(new LineFormatter(null, 'Y-m-d H:i:s', true, true));
        $logger->pushHandler($stream);

        $this->setLogger($logger);
    }

    /**
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
        $this->driver->setLogger($logger);
    }

    /**
     * 发送普通队列消息
     * @param string $exchangeName  交换机名称
     * @param string $queueName     队列名称
     * @param string $routeKey      路由key
     * @param string $message       队列消息
     * @return Message
     */
    public function queue(string $exchangeName, string $queueName, string $routeKey, string $message = ''): Message
    {
        return $this->driver->queue($exchangeName, $queueName, $routeKey, $message);
    }

    /**
     * 发送延时队列消息
     * @param string $exchangeName  交换机名称
     * @param string $queueName     队列名称
     * @param string $routeKey      路由key
     * @param string $message       队列消息
     * @param int $ttl              消息延时时间(秒)
     * @return Message
     */
    public function delayQueue(string $exchangeName, string $queueName, string $routeKey, string $message, int $ttl): Message
    {
        return $this->driver->delayQueue($exchangeName, $queueName, $routeKey, $message, $ttl);
    }

    /**
     * 队列消费者
     * @param Closure $consumer     消费者处理器
     * @param string $exchangeName  交换机名称
     * @param string $queueName     队列名称
     * @param string $routeKey      路由key
     */
    public function consumer(Closure $consumer, string $exchangeName, string $queueName, string $routeKey)
    {
        $this->driver->consumer($consumer, $exchangeName, $queueName, $routeKey, false);
    }

    /**
     * 延时队列消费者
     * @param Closure $consumer     消费者处理器
     * @param string $exchangeName  交换机名称
     * @param string $queueName     队列名称
     * @param string $routeKey      路由key
     */
    public function delayConsumer(Closure $consumer, string $exchangeName, string $queueName, string $routeKey)
    {
        $this->driver->consumer($consumer, $exchangeName, $queueName, $routeKey, true);
    }
}
