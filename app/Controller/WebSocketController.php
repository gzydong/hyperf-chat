<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Swoole\Http\Request;
use Swoole\Websocket\Frame;

use Hyperf\Amqp\Producer;
use App\Amqp\Producer\DemoProducer;

class WebSocketController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    public function onMessage($server, Frame $frame): void
    {
        $producer = container()->get(Producer::class);

        $ip = config('ip_address');
        $producer->produce(new DemoProducer("我是来自[{$ip} 服务器的消息]，{$frame->data}"));
    }

    public function onClose($server, int $fd, int $reactorId): void
    {
        echo PHP_EOL."FD : 【{$fd}】 已断开...";
    }

    public function onOpen($server, Request $request): void
    {
        $ip = config('ip_address');
        $server->push($request->fd, "成功连接[{$ip}],IM 服务器");
        echo PHP_EOL."FD : 【{$request->fd}】 成功连接...";
    }
}
