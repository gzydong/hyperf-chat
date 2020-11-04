<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;

class ChatMessageProducer extends ProducerMessage
{
    public $exchange = 'im.message.fanout';

    public $type = Type::FANOUT;

    public function __construct($data)
    {
        $message = [
            'method'=>'', //
            'sender'=>'', // 发送者
            'receive'=>'', // 接收者
            'receiveType'=>'',
            'message'=>[]
        ];

        $this->payload = $data;
    }
}
