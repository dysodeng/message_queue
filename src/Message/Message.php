<?php

namespace Dy\MessageQueue\Message;

/**
 * 队列消息体
 * @package Dy\MessageQueue\Message
 */
class Message
{
    /**
     * @var string
     */
    private $exchangeName;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var string
     */
    private $routeKey;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $body;

    /**
     * Message constructor.
     * @param string $id
     * @param string $body
     * @param string $exchangeName
     * @param string $queueName
     * @param string $routeKey
     */
    public function __construct(string $id, string $body, string $exchangeName, string $queueName, string $routeKey)
    {
        $this->id = $id;
        $this->body = $body;
        $this->exchangeName = $exchangeName;
        $this->queueName = $queueName;
        $this->routeKey = $routeKey;
    }

    /**
     * 获取交换器名称
     * @return string
     */
    public function getExchangeName(): string
    {
        return $this->exchangeName;
    }

    /**
     * 获取队列名称
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }

    /**
     * 获取路由Key
     * @return string
     */
    public function getRouteKey(): string
    {
        return $this->routeKey;
    }

    /**
     * 获取消息ID
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * 获取消息内容
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * 获取消息体数据
     * @return array
     */
    public function getData(): array
    {
        return [
            'id'            =>  $this->getId(),
            'body'          =>  $this->getBody(),
            'exchange_name' =>  $this->getExchangeName(),
            'queue_name'    =>  $this->getQueueName(),
            'route_key'     =>  $this->getRouteKey(),
        ];
    }
}
