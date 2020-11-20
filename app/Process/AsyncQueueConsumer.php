<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Process;

use Hyperf\AsyncQueue\Process\ConsumerProcess;
use Hyperf\Process\Annotation\Process;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

///**
// * @Process
// */
class AsyncQueueConsumer extends ConsumerProcess
{
    public function handle(): void
    {
        //建立一个到RabbitMQ服务器的连接
        $connection = new AMQPStreamConnection('47.105.180.123', 5672, 'yuandong', 'yuandong','im');
        $channel = $connection->channel();


        $channel->exchange_declare('im.message.fanout', 'fanout', true, false, false);

        list($queue_name, ,) = $channel->queue_declare("test", false, false, true, true);

        $channel->queue_bind($queue_name, 'im.message.fanout','routing-key-test');

        echo ' [*] Waiting for logs. To exit press CTRL+C', "\n";

        $callback = function($msg){
            $data = json_decode($msg->body,true);
            $server = server();
            foreach ($server->connections as $fd) {
                if ($server->exist($fd) && $server->isEstablished($fd)) {
                    $server->push($fd, "Recv: 我是后台进程 [{$data['message']}]");
                }
            }
        };

        $channel->basic_consume($queue_name, 'asfafa', false, true, false, false, $callback);

        while(count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
