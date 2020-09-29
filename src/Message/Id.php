<?php
namespace Dy\MessageQueue\Message;

class Id
{
    /**
     * 创建消息ID
     * @return string
     */
    public static function getId(): string
    {
        $time = microtime(true);
        $ext = explode('.', $time);
        if (isset($ext[1])) {
            $id = $ext[0];
            if (strlen($ext[1]) < 4) {
                $id .= $ext[1] . str_repeat(0, (4 - strlen($ext[1])));
            } else {
                $id .= substr($ext[1], 0, 4);
            }
            return $id.mt_rand(100000, 999999);
        } else {
            return self::getId();
        }
    }
}
