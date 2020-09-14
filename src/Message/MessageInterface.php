<?php
namespace Dy\MessageQueue\Message;

interface MessageInterface
{
    public function __construct(string $message);

    public function handle(): bool;
}
