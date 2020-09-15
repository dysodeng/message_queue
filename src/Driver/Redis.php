<?php
namespace Dy\MessageQueue\Driver;

use Closure;
use Dy\MessageQueue\MessageQueue;
use Exception;

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
        $key = $exchangeName.'.'.$queueName.'.'.$routeKey;
        return $this->conn->xAdd($key, '*', ['payload'=>$message], $this->config['max_len']) ? true : false;
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
        $key = $exchangeName.'.'.$queueName.'.'.$routeKey;

        // 生成唯一消息ID
        $message_id = MessageQueue::createMessageId();

        $this->conn->hMSet($key.'.payload', [$message_id=>$message]); // 将消息存放于hash中
        $this->conn->zAdd($key, ['NX'], time() + $ttl, $message_id); // 将消息id存于zset中，消费时从zset中取出消息id，再从hash中取出消息

        return true;
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
                                $status = call_user_func($consumer, $message);
                                if ($status === true) {
                                    $this->conn->zRem($queueKey, $message_id);
                                    $this->conn->hDel($queueKey.'.payload', $message_id);
                                    $this->conn->sRem($queueKey.'.ack', $message_id);
                                } else {
                                    // 消息重试
                                    $this->conn->sRem($queueKey.'.ack', $message_id);
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
                    logs()->info('redis队列消费者异常：'.$exception->getMessage());
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
                                $status = call_user_func($consumer, $message['payload']);
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
                    logs()->info('redis队列消费者异常：'.$exception->getMessage());
                }
            }
        }
    }

}
