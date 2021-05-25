<?php

namespace App\Cache\Repository;

use App\Cache\Contracts\LockRedisInterface;
use Swoole\Coroutine;
use Exception;

/**
 * Redis Lock
 *
 * @package App\Cache\Repository
 */
class LockRedis extends AbstractRedis implements LockRedisInterface
{
    protected $prefix = 'rds-lock';

    protected $value = 1;

    /**
     * 获取 Redis 锁
     *
     * @param string $key     锁标识
     * @param int    $expired 过期时间/秒
     * @param int    $timeout 获取超时/秒，默认每隔 0.1 秒获取一次锁
     * @return bool
     */
    public function lock(string $key, $expired = 1, int $timeout = 0)
    {
        $lockName = $this->getCacheKey($key);

        // 重复获取次数
        $retry = $timeout > 0 ? intdiv($timeout * 100, 10) : 1;
        do {
            $lock = $this->redis()->set($lockName, $this->value, ['nx', 'ex' => $expired]);
            if ($lock || $timeout === 0) {
                break;
            }

            // 默认 0.1 秒一次取锁
            Coroutine::getCid() ? Coroutine::sleep(0.1) : usleep(100000);

            $retry--;
        } while ($retry);

        return $lock;
    }

    /**
     * 释放 Redis 锁
     *
     * @param string $key
     * @return mixed
     */
    public function delete(string $key)
    {
        $script = <<<LAU
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
LAU;

        return $this->redis()->eval($script, [$this->getCacheKey($key), $this->value], 1);
    }

    /**
     * 获取锁并执行
     *
     * @param \Closure $closure   闭包函数
     * @param string   $lock_name 锁名
     * @param int      $expired   过期时间/秒
     * @param int      $timeout   获取超时/秒
     * @return bool
     * @throws Exception
     */
    public function try(\Closure $closure, string $lock_name, int $expired = 1, int $timeout = 0)
    {
        if (!$this->lock($lock_name, $expired, $timeout)) return false;

        try {
            call_user_func($closure);
        } catch (Exception $e) {
            throw $e;
        } finally {
            $this->delete($lock_name);
        }

        return true;
    }
}
