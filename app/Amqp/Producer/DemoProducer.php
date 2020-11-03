<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use Hyperf\Amqp\Builder\ExchangeBuilder;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;


class DemoProducer extends ProducerMessage
{
    public $exchange = 'im.message.fanout';

    public $type = Type::FANOUT;

    /**
     * 重写创建交换机方法
     * 注释 添加
     *
     * @return ExchangeBuilder
     */
//    public function getExchangeBuilder(): ExchangeBuilder
//    {
//        return parent::getExchangeBuilder()->setAutoDelete(true);
//    }

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
