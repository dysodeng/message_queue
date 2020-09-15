#### 基于Laravel与AMQP的消息队列

## Requirement

1. PHP >= 7.2
2. **[Composer](https://getcomposer.org/)**
3. Laravel >= 7.0
4. ext-amqp 扩展
5. ext-redis 扩展

## Installation

```shell
$ composer require "dy/mq"
```

安装完成后，发布配置文件
```shell
$ php artisan vendor:publish
```

## Usage

配置文件
```php
<?php
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
            'driver'    =>  Dy\MessageQueue\Driver\AMQP::class,
            'callback'  =>  [
                'general'   =>  '', // 实现 Dy\MessageQueue\Message\MessageInterface 接口的普通队列消费者回调
                'delay'     =>  '', // 实现 Dy\MessageQueue\Message\MessageInterface 接口的延时队列消费者回调
            ]
        ],
        // Redis驱动
        'redis'     =>  [
            'host'      =>  env('MQ_REDIS_HOST', '127.0.0.1'),
            'port'      =>  env('MQ_REDIS_PORT', 6379),
            'password'  =>  env('MQ_REDIS_PASSWORD', ''),
            'database'  =>  env('MQ_REDIS_DATABASE', 0),
            'max_len'   =>  env('MQ_REDIS_MAX_LEN', 1000),
            'driver'    =>  Dy\MessageQueue\Driver\Redis::class,
            'callback'  =>  [
                'general'   =>  '', // 实现 Dy\MessageQueue\Message\MessageInterface 接口的普通队列消费者回调
                'delay'     =>  '', // 实现 Dy\MessageQueue\Message\MessageInterface 接口的延时队列消费者回调
            ]
        ]
    ]
];
```

运行列队消费者
```shell
# 普通队列消费者
$ php artisan mq:worker --exchange=test.exchange --queue=test.queue --route=test
```
```shell
# 延时队列消费者
$ php artisan mq:delay_worker --exchange=test.delay.exchange --queue=test.delay.queue --route=test.delay
```

发送消息
```php
<?php
use \Dy\MessageQueue\Facade\MQ;

// 发送普通队列消息
MQ::queue('test.exchange', 'test.queue', 'test', 'hello world');

// 发送延时队列消息，消息会在10秒后投递到消费者
MQ::delayQueue('test.delay.exchange', 'test.delay.queue', 'test.delay', 'hello world', 10);
```
