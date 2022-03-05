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
        ],

        'redis'     =>  [
            'host'      =>  env('MQ_REDIS_HOST', '127.0.0.1'),
            'port'      =>  env('MQ_REDIS_PORT', 6379),
            'password'  =>  env('MQ_REDIS_PASSWORD', ''),
            'database'  =>  env('MQ_REDIS_DATABASE', 0),
            'max_len'   =>  env('MQ_REDIS_MAX_LEN', 1000), // 队列最大长度，只对普通队列有效
        ]
    ],

    'prefix'        =>  '', // key前缀

    'processor'     =>  [], // 实现 Dy\MessageQueue\Processor\ConsumerProcessor 接口的队列消息处理器，用于对接业务逻辑

    'retry'         =>  3,  // 消息失败重试次数

    'log'           =>  [   // 日志
        'level'     =>  'debug',
        'file'      =>  storage_path('logs/dy_message_queue.log')
    ],
];
