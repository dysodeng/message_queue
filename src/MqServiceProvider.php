<?php
namespace Dy\MessageQueue;

use Dy\MessageQueue\Commands\DelayWorker;
use Dy\MessageQueue\Commands\Worker;
use Illuminate\Support\ServiceProvider;

/**
 * MqServiceProvider
 * @package Dy\MessageQueue
 */
class MqServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/message_queue.php',
            'message_queue'
        );

        $this->app->singleton('mq', function ($app) {
            return new MessageQueue($app);
        });
    }

    public function boot()
    {
        // 注册命令行
        if ($this->app->runningInConsole()) {
            $this->commands([
                Worker::class,
                DelayWorker::class
            ]);
        }

        // 配置文件
        $this->publishes([
            __DIR__.'/../config/message_queue.php'  =>  config_path('message_queue.php')
        ]);
    }
}
