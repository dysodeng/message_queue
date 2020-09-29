<?php
namespace Dy\MessageQueue;

use Closure;
use Dy\MessageQueue\Driver\AMQP;
use Dy\MessageQueue\Driver\DriverInterface;
use Dy\MessageQueue\Driver\Redis;
use Dy\MessageQueue\Log\Log;
use Dy\MessageQueue\Message\Message;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class MessageQueue
{
    /**
     * @var Application
     */
    private $app;

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
     * @param Application $app
     * @throws Exception
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $this->app['config']['message_queue'];

        // 创建连接
        $config = $this->config['connections'][$this->config['driver']] ?? [];
        if (empty($config)) {
            throw new Exception($this->config['driver'].' driver not found.');
        }
        $config['retry'] = intval($this->config['retry'] ?? 3); // 重试次数

        // 创建队列驱动
        switch (strtolower($config['driver'])) {
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
     * 队列消息
     * @param $exchangeName
     * @param $queueName
     * @param $routeKey
     * @param string $message
     * @return Message
     */
    public function queue($exchangeName, $queueName, $routeKey, $message = ''): Message
    {
        return $this->driver->queue($exchangeName, $queueName, $routeKey, $message);
    }

    /**
     * 延时队列消息
     * @param string $exchangeName
     * @param string $queueName
     * @param string $routeKey
     * @param string $message
     * @param int $ttl
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
