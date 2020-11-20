<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Redis\Redis;
use PhpAmqpLib\Message\AMQPMessage;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Builder\QueueBuilder;
use Hyperf\Utils\Coroutine;

/**
 * @Consumer(name="聊天消息消费者",enable=true)
 */
class ChatMessageConsumer extends ConsumerMessage
{
    /**
     * 交换机名称
     *
     * @var string
     */
    public $exchange = 'im.message.fanout';

    /**
     * 交换机类型
     *
     * @var string
     */
    public $type = Type::FANOUT;

    /**
     * 路由key
     *
     * @var string
     */
    public $routingKey = 'consumer:im:message';

    /**
     * ImMessageConsumer constructor.
     */
    public function __construct()
    {
        $this->setQueue('queue:im-message:' . SERVER_RUN_ID);
    }

    /**
     * 重写创建队列生成类
     *
     * 注释：设置自动删除队列
     *
     * @return QueueBuilder
     */
    public function getQueueBuilder(): QueueBuilder
    {
        return parent::getQueueBuilder()->setAutoDelete(true);
    }

    /**
     * 消费队列消息
     *
     * @param $data
     * @param AMQPMessage $message
     * @return string
     */
    public function consumeMessage($data, AMQPMessage $message): string
    {

        go(function() use ($data) {
            $redis = container()->get(Redis::class);

            //[加锁]防止消息重复消费
            $lockName = sprintf('ws:message-lock:%s:%s', SERVER_RUN_ID, $data['uuid']);
            if (!$redis->rawCommand('SET', $lockName, 1, 'NX', 'EX', 60)) {
                return Result::ACK;
            }

            $server = server();
            foreach ($server->connections as $fd) {
                if ($server->exist($fd) && $server->isEstablished($fd)) {
                    $server->push($fd, "Recv: 我是后台进程 [{$data['message']}]");
                }
            }
        });


        return Result::ACK;
    }
}
