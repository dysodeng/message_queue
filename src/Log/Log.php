<?php
namespace Dy\MessageQueue\Log;

use Monolog\Logger as Monolog;

class Log
{
    protected static $levels = [
        'debug' => Monolog::DEBUG,
        'info' => Monolog::INFO,
        'notice' => Monolog::NOTICE,
        'warning' => Monolog::WARNING,
        'error' => Monolog::ERROR,
        'critical' => Monolog::CRITICAL,
        'alert' => Monolog::ALERT,
        'emergency' => Monolog::EMERGENCY,
    ];

    /**
     * @param string $level
     * @return int
     */
    public static function getLevel($level = 'debug'): int
    {
        return self::$levels[$level] ?? self::$levels['debug'];
    }
}
