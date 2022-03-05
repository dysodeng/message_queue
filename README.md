#### 基于Laravel与AMQP的消息队列

## Requirement

1. PHP >= 7.2
2. **[Composer](https://getcomposer.org/)**
3. Laravel >= 7.0
4. ext-amqp 扩展
5. ext-redis 扩展
6. 启用amqp时，RabbitMQ需要安装 [rabbitmq_delayed_message_exchange](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange/releases) 插件

## Installation

```shell
$ composer require "dy/mq"
```

安装完成后，发布配置文件
```shell
$ php artisan vendor:publish # 选择 Provider: Dy\MessageQueue\ServiceProvider
```

## Usage

消息处理器

```php
<?php

use Dy\MessageQueue\Message\Message;
use Dy\MessageQueue\Processor\ConsumerProcessor;

class DemoMessageProcessor implements ConsumerProcessor
{
    public function handle(Message $message): bool
    {
        var_dump($message->getData());
        return true;
    }
}

class DemoDelayMessageProcessor implements ConsumerProcessor
{
    public function handle(Message $message): bool
    {
        var_dump($message->getData());
        return true;
    }
}
```

配置文件
```php
<?php
// config/message_queue.php
return [
    // 默认MQ驱动，与connections对应
    'driver'        =>  env('MQ_DRIVER', 'amqp'),

    'connections'   =>  [
        // AMQP驱动
        'amqp'      =>  [
            'host'      =>  env('MQ_AMQP_HOST', '127.0.0.1'),
            'port'      =>  env('MQ_AMQP_PORT', 5672),
            'user'      =>  env('MQ_AMQP_USER', 'guest'),
            'password'  =>  env('MQ_AMQP_PASSWORD', 'guest'),
            'vhost'     =>  env('MQ_AMQP_VHOST', '/'),
        ],
        // Redis驱动
        'redis'     =>  [
            'host'      =>  env('MQ_REDIS_HOST', '127.0.0.1'),
            'port'      =>  env('MQ_REDIS_PORT', 6379),
            'password'  =>  env('MQ_REDIS_PASSWORD', ''),
            'database'  =>  env('MQ_REDIS_DATABASE', 0),
            'max_len'   =>  env('MQ_REDIS_MAX_LEN', 1000),
        ]
    ],

    'prefix'        =>  '', // key前缀

    'processor'     =>  [ // 实现 Dy\MessageQueue\Processor\ConsumerProcessor 接口的队列消息处理器，用于对接业务逻辑
        'demo'      =>  DemoMessageProcessor::class,
        'demo.delay'=>  DemoDelayMessageProcessor::class,
    ],
    
    'retry'         =>  3,  // 消息失败重试次数

    'log'           =>  [   // 日志
        'level'     =>  'debug',
        'file'      =>  storage_path('logs/dy_message_queue.log')
    ],
];
```

运行列队消费者
```shell
# 普通队列消费者
$ php artisan mq:worker --exchange=test.exchange --queue=test.queue --route=test --processor=demo
```
```shell
# 延时队列消费者
$ php artisan mq:delay_worker --exchange=test.delay.exchange --queue=test.delay.queue --route=test.delay --processor=demo.delay
```

发送消息
```php
<?php
use \Dy\MessageQueue\Facade\MQ;

// 发送普通队列消息，消息将被立即投递到消费者
MQ::queue('test.exchange', 'test.queue', 'test', 'hello world');

// 发送延时队列消息，消息会在10秒后投递到消费者
MQ::delayQueue('test.delay.exchange', 'test.delay.queue', 'test.delay', 'hello world', 10);
```
