<?php
namespace Dy\MessageQueue;

use Dy\MessageQueue\Driver\DriverInterface;
use Closure;
use Exception;
use Illuminate\Contracts\Foundation\Application;

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
            throw new Exception('mq not found driver on "'.$this->config['driver'].'".');
        }

        $this->driver = new $config['driver']($config);
    }

    /**
     * 队列消息
     * @param $exchangeName
     * @param $queueName
     * @param $routeKey
     * @param string $message
     * @return mixed
     */
    public function queue($exchangeName, $queueName, $routeKey, $message = '')
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
     * @return mixed
     */
    public function delayQueue(string $exchangeName, string $queueName, string $routeKey, string $message, int $ttl)
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
        if (in_array($this->config['driver'], ['amqp'])) {
            $exchangeName .= '.delay';
            $queueName .= '.delay';
        }
        $this->driver->consumer($consumer, $exchangeName, $queueName, $routeKey, true);
    }
}
