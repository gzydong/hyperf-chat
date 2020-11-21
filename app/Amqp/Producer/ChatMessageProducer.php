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

    public function __construct($sender, $receive, $source, $record_id)
    {
        $message = [
            'uuid' => $this->uuid(),
            'sender' => intval($sender),  //发送者ID
            'receive' => intval($receive),  //接收者ID
            'source' => intval($source), //接收者类型 1:好友;2:群组
            'record_id' => intval($record_id)
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
