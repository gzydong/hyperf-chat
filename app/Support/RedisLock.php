<?php

namespace App\Support;

/**
 * Class RedisLock
 * @package App\Support
 */
class RedisLock
{

    /**
     * 锁前缀标识
     */
    const PREFIX = 'lock';

    /**
     * 获取Redis连接
     *
     * @return mixed|\Redis
     */
    public static function getRedis()
    {
        return redis();
    }

    /**
     * 获得锁,如果锁被占用,阻塞,直到获得锁或者超时。
     * -- 1、如果 $timeout 参数为 0,则立即返回锁。
     * -- 2、建议 timeout 设置为 0,避免 redis 因为阻塞导致性能下降。请根据实际需求进行设置。
     *
     * @param string $key 缓存KEY
     * @param string|int $requestId 客户端请求唯一ID
     * @param integer $lockSecond 锁定时间 单位(秒)
     * @param integer $timeout 取锁超时时间。单位(秒)。等于0,如果当前锁被占用,则立即返回失败。如果大于0,则反复尝试获取锁直到达到该超时时间。
     * @param integer|float $sleep 取锁间隔时间 单位(秒)。当锁为占用状态时。每隔多久尝试去取锁。默认 0.1 秒一次取锁。
     * @return bool
     * @throws \Exception
     */
    public static function lock(string $key,$requestId, $lockSecond = 20, $timeout = 0, $sleep = 0.1)
    {
        if (empty($key)) {
            throw new \Exception('获取锁的KEY值没有设置');
        }

        $start = self::getMicroTime();
        $redis = self::getRedis();

        do {
            $acquired = $redis->rawCommand('SET', self::getLockKey($key), $requestId, 'NX', 'EX', $lockSecond);
            if ($acquired) {
                break;
            }

            if ($timeout === 0) {
                break;
            }

            \Swoole\Coroutine\System::sleep($sleep);
        } while (!is_numeric($timeout) || (self::getMicroTime()) < ($start + ($timeout * 1000000)));

        return $acquired ? true : false;
    }

    /**
     * 释放锁
     *
     * @param string $key 被加锁的KEY
     * @param string|int $requestId 客户端请求唯一ID
     * @return bool
     */
    public static function release(string $key,$requestId)
    {
        if (strlen($key) === 0) {
            return false;
        }

        $lua = <<<LAU
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
LAU;

        self::getRedis()->rawCommand('eval', $lua, 1, self::getLockKey($key), $requestId);
    }

    /**
     * 获取锁 Key
     *
     * @param string $key 需要加锁的KEY
     * @return string
     */
    public static function getLockKey(string $key)
    {
        return self::PREFIX . ':' . $key;
    }

    /**
     * 获取当前微秒
     *
     * @return string
     */
    protected static function getMicroTime()
    {
        return bcmul(microtime(true), 1000000);
    }
}
