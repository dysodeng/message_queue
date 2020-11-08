<?php
namespace Dy\MessageQueue\Commands;

use Dy\MessageQueue\Facade\MQ;
use Dy\MessageQueue\Message\Message;
use Dy\MessageQueue\Message\MessageProcessorInterface;
use Illuminate\Console\Command as BaseCommand;

/**
 * 延时队列工作处理器
 * @package Dy\MessageQueue\Commands
 */
class DelayWorker extends BaseCommand
{
    protected $signature = 'mq:delay_worker {--exchange= : 交换机名称} {--queue= : 队列名称} {--route= : 路由Key} {--processor= : 消息处理器}';

    protected $description = '运行mq延时队列消费者';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        MQ::delayConsumer(function (Message $message) {
            $config = config('message_queue');
            $processor_list = $config['processor'] ?? [];
            $processor = $this->option('processor') ?? '';
            if (isset($processor_list[$processor])) {
                if ($processor_list[$processor] && class_exists($processor_list[$processor])) {
                    $ins = new $processor_list[$processor]();
                    if ($ins instanceof MessageProcessorInterface) {
                        return $ins->handle($message);
                    }
                }
            }
            return true;
        }, $this->option('exchange'), $this->option('queue'), $this->option('route'));
    }
}
