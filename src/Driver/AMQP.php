<?php
namespace Dy\MessageQueue\Driver;

use AMQPChannel;
use AMQPChannelException;
use AMQPConnection;
use AMQPConnectionException;
use AMQPEnvelope;
use AMQPEnvelopeException;
use AMQPExchange;
use AMQPExchangeException;
use AMQPQueue;
use AMQPQueueException;
use Closure;
use Dy\MessageQueue\Message\Id;
use Dy\MessageQueue\Message\Message;
use Exception;

/**
 * 消息队列 AMQP驱动实现
 * @package Dy\MessageQueue\Driver
 */
class AMQP implements DriverInterface
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * @var AMQPConnection
     */
    private $conn;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * AMQP constructor.
     * @param array $config
     * @throws AMQPConnectionException
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->connection();
    }

    /**
     * @throws AMQPConnectionException
     */
    private function connection()
    {
        $this->conn = new AMQPConnection([
            'host'      =>  $this->config['host'] ?? '',
            'port'      =>  $this->config['port'] ?? 5672,
            'vhost'     =>  $this->config['vhost'] ?? '/',
            'login'     =>  $this->config['user'] ?? '',
            'password'  =>  $this->config['password'] ?? ''
        ]);

        $this->conn->connect();
        $this->channel = new AMQPChannel($this->conn);
    }

    /**
     * 队列消息
     * @param string $exchangeName      交换机名称
     * @param string $queueName         队列名称
     * @param string $routeKey          路由key
     * @param string $message           队列消息
     * @return Message
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     * @throws AMQPExchangeException
     * @throws Exception
     */
    public function queue(string $exchangeName, string $queueName, string $routeKey, string $message): Message
    {
        $exchange = new AMQPExchange($this->channel);
        $exchange->setName($exchangeName);
        $exchange->setType(AMQP_EX_TYPE_DIRECT);
        $exchange->setFlags(AMQP_DURABLE);
        if (!$exchange->declareExchange()) {
            throw new Exception('exchange declaration failure');
        }

        $message_id = Id::getId();

        if (!$exchange->publish($message, $routeKey, AMQP_NOPARAM, ['message_id'=>$message_id])) {
            throw new Exception('message publish failure.');
        }

        return new Message(
            $message_id,
            $message,
            $exchangeName,
            $queueName,
            $routeKey
        );
    }

    /**
     * 延时队列消息
     * @param string $exchangeName      交换机名称
     * @param string $queueName         队列名称
     * @param string $routeKey          路由key
     * @param string $message           队列消息
     * @param int $ttl                  消息延时时间(秒)
     * @return Message
     * @throws AMQPConnectionException
     * @throws AMQPExchangeException
     * @throws AMQPQueueException
     * @throws AMQPChannelException
     * @throws Exception
     */
    public function delayQueue(string $exchangeName, string $queueName, string $routeKey, string $message, int $ttl): Message
    {
        $exchange = new AMQPExchange($this->channel);
        $exchange->setName($exchangeName);
        $exchange->setType('x-delayed-message');
        $exchange->setFlags(AMQP_DURABLE);
        $exchange->setArgument('x-delayed-type', 'direct');
        if (!$exchange->declareExchange()) {
            throw new Exception('exchange declaration failure', 1);
        }

        $queue = new AMQPQueue($this->channel);
        $queue->setName($queueName);
        $queue->setFlags(AMQP_DURABLE);
        $queue->declareQueue();

        $queue->bind($exchangeName, $routeKey);

        $message_id = Id::getId();

        if (!$exchange->publish($message, $routeKey, AMQP_NOPARAM, [
            'message_id'    =>  $message_id,
            'headers'       =>  [
                'x-delay'   =>  $ttl * 1000
            ]
        ])) {
            throw new Exception('message publish failure.');
        }

        return new Message(
            $message_id,
            $message,
            $exchangeName,
            $queueName,
            $routeKey
        );
    }

    /**
     * 队列消费者
     * @param Closure $consumer         消费者处理器
     * @param string $exchangeName      交换机名称
     * @param string $queueName         队列名称
     * @param string $routeKey          路由key
     * @param bool $is_delay            是否延时任务
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     * @throws AMQPExchangeException
     * @throws AMQPEnvelopeException
     * @throws AMQPQueueException
     * @throws Exception
     */
    public function consumer(Closure $consumer, string $exchangeName, string $queueName, string $routeKey, bool $is_delay = false)
    {
        $exchange = new AMQPExchange($this->channel);
        $exchange->setName($exchangeName);
        if ($is_delay) {
            $exchange->setType('x-delayed-message');
            $exchange->setArgument('x-delayed-type','direct');
        } else {
            $exchange->setType(AMQP_EX_TYPE_DIRECT);
        }
        $exchange->setFlags(AMQP_DURABLE);

        if (!$exchange->declareExchange()) {
            throw new Exception('exchange declaration failure', 1);
        }

        $queue = new AMQPQueue($this->channel);
        $queue->setName($queueName);
        $queue->setFlags(AMQP_DURABLE);
        $queue->declareQueue();

        $queue->bind($exchangeName, $routeKey);

        echo '[*] Waiting for messages. To exit press CTRL+C', "\n";

        while (true) {
            $queue->consume(function (AMQPEnvelope $envelope, AMQPQueue $queue) use ($consumer, $queueName) {
                $message = new Message(
                    $envelope->getMessageId(),
                    $envelope->getBody(),
                    $envelope->getExchangeName(),
                    $queueName,
                    $envelope->getRoutingKey()
                );
                $status = call_user_func($consumer, $message);
                if ($status === true) {
                    $queue->ack($envelope->getDeliveryTag());
                }
            });
        }
    }

    public function __destruct()
    {
        $this->conn->disconnect();
    }
}
