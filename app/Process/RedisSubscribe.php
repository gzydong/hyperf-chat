<?php

declare(strict_types=1);

namespace App\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;

/**
 * @Process(name="RedisSubscribe")
 */
class RedisSubscribe extends AbstractProcess
{
    /**
     * 订阅的通道
     *
     * @var string[]
     */
    private $chans = ['websocket'];

    public function handle(): void
    {
        redis()->subscribe($this->chans, [$this, 'subscribe']);
    }

    /**
     * 订阅处理逻辑
     *
     * @param        $redis
     * @param string $chan
     * @param string $msg
     */
    public function subscribe($redis, string $chan, string $msg)
    {
        echo PHP_EOL . "chan : $chan , msg : $msg";
    }

    public function isEnable($server): bool
    {
        return false;
    }
}
