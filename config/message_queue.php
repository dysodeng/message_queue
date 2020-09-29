<?php

return [

    'driver'        =>  env('MQ_DRIVER', 'amqp'),

    'connections'   =>  [

        'amqp'      =>  [
            'host'      =>  env('MQ_AMQP_HOST', '127.0.0.1'),
            'port'      =>  env('MQ_AMQP_PORT', 5672),
            'user'      =>  env('MQ_AMQP_USER', 'guest'),
            'password'  =>  env('MQ_AMQP_PASSWORD', 'guest'),
            'vhost'     =>  env('MQ_AMQP_VHOST', '/'),
            'driver'    =>  Dy\MessageQueue\Driver\AMQP::class
        ],

        'redis'     =>  [
            'host'      =>  env('MQ_REDIS_HOST', '127.0.0.1'),
            'port'      =>  env('MQ_REDIS_PORT', 6379),
            'password'  =>  env('MQ_REDIS_PASSWORD', ''),
            'database'  =>  env('MQ_REDIS_DATABASE', 0),
            'max_len'   =>  env('MQ_REDIS_MAX_LEN', 1000), // 队列最大长度，只对普通队列有效
            'driver'    =>  Dy\MessageQueue\Driver\Redis::class
        ]
    ],

    'callback'      =>  '', // 实现 Dy\MessageQueue\Message\MessageInterface 接口的队列消费者回调，用于对接业务逻辑
];
