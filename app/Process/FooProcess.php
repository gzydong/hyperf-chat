<?php
declare(strict_types=1);

namespace App\Process;

use Hyperf\Logger\Logger;
use Hyperf\Process\AbstractProcess;
use Hyperf\Utils\ApplicationContext;
use Swoole\WebSocket\Server as WebSocketServer;
use Hyperf\Server\ServerFactory;


use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;



class FooProcess extends AbstractProcess
{
    public function handle(): void
    {
//        while (true){
//
//            echo 'websocket ...'.PHP_EOL;
//            $server = $this->container->get(ServerFactory::class)->getServer()->getServer();
//
//            $fds = [];
//            foreach ($server->connections as $fd) {
//                if ($server->isEstablished($fd)) {
//                    $fds[] = $fd;
//
//                    $server->push($fd, 'Recv: 我是后台进程 ...');
//                }
//            }
//
//            echo "当前连接数：".count($fds).' -- fids: '.implode(',',$fds).PHP_EOL;
//
//
//            sleep(3);
//            echo time().PHP_EOL;
//        }

        // 您的代码 ...
    }
}
