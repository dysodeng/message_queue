<?php
namespace Dy\MessageQueue\Message;

/**
 * 消息处理器接口
 * @package Dy\MessageQueue\Message
 */
interface MessageProcessorInterface
{
    public function handle(Message $message): bool;
}
