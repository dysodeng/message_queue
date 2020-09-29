<?php
namespace Dy\MessageQueue\Message;

interface MessageInterface
{
    public function __construct(Message $message);

    public function handle(): bool;
}
