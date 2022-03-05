<?php

namespace Dy\MessageQueue\Driver;

use Closure;
use Dy\MessageQueue\Message\Id;
use Dy\MessageQueue\Message\Message;
use Exception;
use Monolog\Logger;

/**
 * 消息队列 Redis驱动实现
 * 普通队列使用：redis的stream 类型实现
 * 延时队列使用：zset实现延时，hash保存消息，set集合实现消息确认机制
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
     * @var Logger
     */
    private $logger;

    /**
     * @var array
     */
    private $retry_message_count = [];

    /**
     * Redis constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->connection();
    }

    private function connection()
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
     * 设置日志处理器
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 发送普通队列消息
     * @param string $exchangeName      交换机名称
     * @param string $queueName         队列名称
     * @param string $routeKey          路由key
     * @param string $message           队列消息
     * @return Message
     */
    public function queue(string $exchangeName, string $queueName, string $routeKey, string $message): Message
    {
        $key = $exchangeName.'.'.$queueName.'.'.$routeKey;
        $messageId = $this->conn->xAdd($key, '*', ['payload'=>$message], $this->config['max_len']);

        return new Message(
            $messageId,
            $message,
            $exchangeName,
            $queueName,
            $routeKey
        );
    }

    /**
     * 发送延时队列消息
     * @param string $exchangeName      交换机名称
     * @param string $queueName         队列名称
     * @param string $routeKey          路由key
     * @param string $message           队列消息
     * @param int $ttl                  消息生存时间(秒)
     * @return Message
     */
    public function delayQueue(string $exchangeName, string $queueName, string $routeKey, string $message, int $ttl): Message
    {
        $key = $exchangeName.'.'.$queueName.'.'.$routeKey;

        // 生成唯一消息ID
        $message_id = Id::getId();

        $this->conn->hMSet($key.'.payload', [$message_id=>$message]); // 将消息存放于hash中
        $this->conn->zAdd($key, ['NX'], time() + $ttl, $message_id); // 将消息id存于zset中，消费时从zset中取出消息id，再从hash中取出消息

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
     */
    public function consumer(Closure $consumer, string $exchangeName, string $queueName, string $routeKey, bool $is_delay = false)
    {
        ini_set('default_socket_timeout', -1);
        $this->conn->setOption(\Redis::OPT_READ_TIMEOUT, -1);

        if ($is_delay) {
            $queueKey = $exchangeName.'.'.$queueName.'.'.$routeKey;

            echo '[*] Waiting for messages. To exit press CTRL+C', "\n";

            $offset = 0;

            while (true) {
                $message_id = '';
                try {
                    $result = $this->conn->zRangeByScore($queueKey, 0, time(), ['withscores'=>true, 'limit'=>[$offset, 1]]); // 取一条
                    if ($result) {
                        $keys = array_keys($result);
                        $message_id = array_pop($keys);
                        if ($message_id) {
                            if (!$this->conn->sIsMember($queueKey.'.ack', $message_id)) {
                                $this->conn->sAdd($queueKey.'.ack', $message_id);

                                // 取出消息内容
                                $message = $this->conn->hGet($queueKey.'.payload', $message_id);
                                $status = call_user_func($consumer, new Message(
                                    $message_id,
                                    $message,
                                    $exchangeName,
                                    $queueName,
                                    $routeKey
                                ));
                                if ($status === true) {
                                    $this->conn->zRem($queueKey, $message_id);
                                    $this->conn->hDel($queueKey.'.payload', $message_id);
                                    $this->conn->sRem($queueKey.'.ack', $message_id);
                                } else {
                                    // 消息重试
                                    $mark = $exchangeName.$queueName.$routeKey.$message_id;
                                    $count = $this->getRetryCount($mark);

                                    $time = date('Y-m-d H:i:s');

                                    if ($count < $this->config['retry']) {
                                        $this->conn->sRem($queueKey.'.ack', $message_id);
                                        echo '[Time: '.$time.' MessageId: '.$message_id.']消息重试中...', "\n";
                                    } else {
                                        $this->conn->zRem($queueKey, $message_id);
                                        $this->conn->hDel($queueKey.'.payload', $message_id);
                                        $this->conn->sRem($queueKey.'.ack', $message_id);

                                        echo '[Time: '.$time.' MessageId: '.$message_id.']'.'消息处理失败', "\n";
                                        $this->logger->error('消息处理失败', [
                                            'ExchangeName'  =>  $exchangeName,
                                            'QueueName'     =>  $queueName,
                                            'RouteKey'      =>  $routeKey,
                                            'MessageId'     =>  $message_id,
                                            'Body'          =>  $message,
                                            'Time'          =>  $time,
                                        ]);
                                    }
                                }
                                $offset = 0;
                            } else {
                                $offset += 1;
                            }
                        }
                    }
                } catch (Exception $exception) {
                    // 消息重试
                    $this->conn->sRem($queueKey.'.ack', $message_id);
                    $this->logger->error('redis队列消费者异常：'.$exception->getMessage());
                }
            }
        } else {
            $queueKey = $exchangeName.'.'.$queueName.'.'.$routeKey;

            // 创建消息组，把交换机当作消息组名称
            $this->queue($exchangeName, $queueName, $routeKey, 'queue_test_message'); // 创建组之前，需要先创建队列
            $this->conn->xGroup('CREATE', $queueKey, $exchangeName, 0);

            echo '[*] Waiting for messages. To exit press CTRL+C', "\n";

            while (true) {
                try {
                    $result = $this->conn->xReadGroup($exchangeName, $queueName.time(), [$queueKey=>'>'], 1, 0);
                    if ($result) {
                        if (isset($result[$queueKey])) {
                            $message = [];
                            foreach ($result[$queueKey] as $key => $item) {
                                $message = [
                                    'message_id'=>  $key,
                                    'payload'   =>  $item['payload']
                                ];
                                break;
                            }

                            if ($message['payload'] == 'queue_test_message') {
                                echo '[*] test for messages.', "\n";
                                $this->conn->xAck($queueKey, $exchangeName, [$message['message_id']]);
                            } else {
                                $status = call_user_func($consumer, new Message(
                                    $message['message_id'],
                                    $message['payload'],
                                    $exchangeName,
                                    $queueName,
                                    $routeKey
                                ));
                                if ($status === true) {
                                    $this->conn->xAck($queueKey, $exchangeName, [$message['message_id']]);
                                } else {
                                    // TODO 消息重试
                                }
                            }
                        }
                    }
                } catch (Exception $exception) {
                    // TODO 消息重试
                    $this->logger->info('redis队列消费者异常：'.$exception->getMessage());
                }
            }
        }
    }

    /**
     * 获取消息重试次数
     * @param string $mark
     * @param bool $is_clear
     * @return int
     */
    private function getRetryCount(string $mark, bool $is_clear = false): int
    {
        $count = 0;
        if (isset($this->retry_message_count[$mark])) {
            $count = $this->retry_message_count[$mark];
        } else {
            $this->retry_message_count[$mark] = 0;
        }

        $this->retry_message_count[$mark] += 1;

        if ($is_clear) {
            unset($this->retry_message_count[$mark]);
        }

        return $count;
    }
}
