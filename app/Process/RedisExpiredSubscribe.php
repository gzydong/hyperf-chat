<?php
declare(strict_types=1);

namespace App\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;

/**
 * @Process(name="RedisExpiredSubscribe")
 */
class RedisExpiredSubscribe extends AbstractProcess
{
    public function handle(): void
    {
        redis()->psubscribe(['__keyevent@5__:expired'], [$this, 'subscribe']);
    }

    /**
     * 过期键回调处理
     *
     * @param $redis
     * @param $pattern
     * @param $chan
     * @param $msg
     */
    public function subscribe($redis, $pattern, $chan, $msg)
    {
        echo "Pattern: $pattern\n";
        echo "Channel: $chan\n";
        echo "Payload: $msg\n\n";
    }

    public function isEnable($server): bool
    {
        return false;
    }
}
