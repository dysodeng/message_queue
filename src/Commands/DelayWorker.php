<?php
namespace Dy\MessageQueue\Commands;

use Dy\MessageQueue\Facade\MQ;
use Illuminate\Console\Command as BaseCommand;

class DelayWorker extends BaseCommand
{
    protected $signature = 'mq:delay_worker {--exchange= : 交换机名称} {--queue= : 队列名称} {--route= : 路由Key}';

    protected $description = '运行mq延时队列消费者';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        MQ::delayConsumer(function (string $message) {
            var_dump($message);
            return true;
        }, $this->option('exchange'), $this->option('queue'), $this->option('route'));
    }
}
