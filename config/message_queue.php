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
            'driver'    =>  Dy\MessageQueue\Driver\AMQP::class,
            'callback'  =>  [
                'general'   =>  '',
                'delay'     =>  ''
            ]
        ],

        'redis'     =>  [
            'host'      =>  env('MQ_REDIS_HOST', '127.0.0.1'),
            'port'      =>  env('MQ_REDIS_PORT', 6379),
            'password'  =>  env('MQ_REDIS_PASSWORD', ''),
            'database'  =>  env('MQ_REDIS_DATABASE', 0),
            'max_len'   =>  env('MQ_REDIS_MAX_LEN', 1000), // 队列最大长度，只对普通队列有效
            'driver'    =>  Dy\MessageQueue\Driver\Redis::class,
            'callback'  =>  [
                'general'   =>  '',
                'delay'     =>  ''
            ]
        ]
    ]
];
