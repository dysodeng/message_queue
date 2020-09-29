<?php
namespace Dy\MessageQueue\Commands;

use Dy\MessageQueue\Facade\MQ;
use Dy\MessageQueue\Message\Message;
use Dy\MessageQueue\Message\MessageInterface;
use Illuminate\Console\Command as BaseCommand;

class Worker extends BaseCommand
{
    protected $signature = 'mq:worker {--exchange= : 交换机名称} {--queue= : 队列名称} {--route= : 路由Key}';

    protected $description = '运行mq队列消费者';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        MQ::consumer(function (Message $message) {
            $config = config('message_queue');
            $callback = $config['callback'] ?? '';
            if ($callback && class_exists($callback)) {
                $ins = new $callback($message);
                if ($ins instanceof MessageInterface) {
                    return $ins->handle();
                }
            }
            return true;
        }, $this->option('exchange'), $this->option('queue'), $this->option('route'));
    }
}
