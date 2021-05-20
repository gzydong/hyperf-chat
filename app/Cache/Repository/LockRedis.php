<?php

namespace App\Cache\Repository;

use App\Cache\Contracts\LockRedisInterface;

/**
 * Redis Lock
 *
 * @package App\Cache\Repository
 */
class LockRedis implements LockRedisInterface
{
    use RedisTrait;

    private $prefix = 'rds:lock';

    private $lockValue = 1;

    /**
     * 获取是毫秒时间戳
     *
     * @return int
     */
    private function time()
    {
        return intval(microtime(true) * 1000);
    }

    /**
     * 获取 Redis 锁
     *
     * @param string $key      锁标识
     * @param int    $lockTime 过期时间/秒
     * @param int    $timeout  获取超时/毫秒
     * @return bool
     */
    public function lock(string $key, $lockTime = 1, $timeout = 0)
    {
        $lockName = $this->getCacheKey($key);

        $start = $this->time();
        do {
            $lock = $this->redis()->set($lockName, $this->lockValue, ['nx', 'ex' => $lockTime]);
            if ($lock || $timeout === 0) {
                break;
            }

            // 默认 0.1 秒一次取锁
            usleep(100000);
        } while ($this->time() < $start + $timeout);

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

        return $this->redis()->eval($script, [$this->getCacheKey($key), $this->lockValue,], 1);
    }
}
