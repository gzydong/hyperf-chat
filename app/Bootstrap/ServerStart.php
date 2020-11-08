<?php

namespace App\Bootstrap;

use App\Service\SocketFDService;
use Hyperf\Framework\Bootstrap\ServerStartCallback;
use Hashids\Hashids;
use Swoole\Timer;
use Hyperf\Redis\Redis;

/**
 * 自定义服务启动前回调事件
 *
 * Class ServerStart
 * @package App\Bootstrap
 */
class ServerStart extends ServerStartCallback
{
    /**
     * 回调事件
     */
    public function beforeStart()
    {
        stdout_log()->info(sprintf('服务运行ID : %s', SERVER_RUN_ID));

        $this->setTimeOut();
        Timer::tick(15000, function () {
            $this->setTimeOut();
        });
    }

    public function setTimeOut()
    {
        container()->get(Redis::class)->hset('SERVER_RUN_ID', SERVER_RUN_ID, time());
    }
}
