<?php
declare(strict_types=1);

namespace App\Support;

use App\Constant\RedisSubscribeChan;

class Message
{
    /**
     * @param string $event
     * @param array  $data
     * @param array  $options
     * @return array
     */
    public static function create(string $event, array $data, array $options = []): array
    {
        return [
            'uuid'    => uniqid((strval(mt_rand(0, 1000)))),
            'event'   => $event,
            'data'    => $data,
            'options' => $options
        ];
    }

    /**
     * 推送消息至 Redis 订阅通道中
     *
     * @param array $message
     */
    public static function publish(array $message)
    {
        push_redis_subscribe(RedisSubscribeChan::WEBSOCKET_CHAN, $message);
    }
}
