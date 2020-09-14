<?php
namespace Dy\MessageQueue;

use App\Console\Commands\MqDelayWorker;
use App\Console\Commands\MqWorker;
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

        $this->app->singleton(MessageQueue::class, function ($app) {
            return new MessageQueue($app);
        });
    }

    public function boot()
    {
        // 配置文件
        $this->publishes([
            __DIR__.'/../config/message_queue.php'  =>  config_path('message_queue.php')
        ]);

        // 命令行文件
        $this->publishes([
            __DIR__.'/Commands/MqDelayWorker.php'   =>  app_path('Console/Commands/MqDelayWorker.php'),
            __DIR__.'/Commands/MqWorker.php'        =>  app_path('Console/Commands/MqWorker.php')
        ]);

        // 注册命令行
        if ($this->app->runningInConsole()) {
            $this->commands([
                MqWorker::class,
                MqDelayWorker::class
            ]);
        }
    }
}
