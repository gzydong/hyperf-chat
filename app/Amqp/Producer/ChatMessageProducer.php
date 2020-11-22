<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;
use Hyperf\Utils\Str;

class ChatMessageProducer extends ProducerMessage
{
    public $exchange = 'im.message.fanout';

    public $type = Type::FANOUT;

    const EVENTS = [
        'event_talk',
        'event_keyboard',
        'event_online_status',
        'event_revoke_talk'
    ];

    /**
     * 初始化处理...
     *
     * @param string $event 事件名
     * @param array $data 数据
     * @param array $options 其它参数
     */
    public function __construct(string $event, array $data, array $options = [])
    {
        if (!in_array($event, self::EVENTS)) {
            new \Exception('事件名未注册...');
        }

        $message = [
            'uuid' => $this->uuid(),// 自定义消息ID
            'event' => $event,
            'data' => $data,
            'options' => $options
        ];

        $this->payload = $message;
    }

    /**
     * 生成唯一ID
     *
     * @return string
     */
    private function uuid()
    {
        return Str::random() . rand(100000, 999999) . uniqid();
    }
}
