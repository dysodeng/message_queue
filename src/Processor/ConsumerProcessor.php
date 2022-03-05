<?php

namespace Dy\MessageQueue\Processor;

use Dy\MessageQueue\Message\Message;

/**
 * 消息消息者处理器接口
 * @package Dy\MessageQueue\Message
 */
interface ConsumerProcessor
{
    public function handle(Message $message): bool;
}
