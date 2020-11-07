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

    public function __construct($data)
    {
        $message = [
            'uuid' => $this->uuid(),
            'method' => '', //
            'sender' => '',  //发送者ID
            'receive' => '',  //接收者ID
            'receiveType' => '', //接收者类型 1:好友;2:群组
            'message' => []
        ];

        $this->payload = $data;
    }

    /**
     * 生成唯一ID
     *
     * @return string
     */
    private function uuid()
    {
        return Str::random() . rand(100000, 999999);
    }
}
