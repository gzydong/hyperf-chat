<?php
declare(strict_types=1);

namespace App\Cache\Repository;

use Hyperf\Redis\Redis;

abstract class AbstractRedis
{
    protected $prefix = 'rds';

    protected $name = '';

    /**
     * 静态方法调用(获取子类实例)
     *
     * @return static
     */
    public static function getInstance()
    {
        return di()->get(static::class);
    }

    /**
     * 获取 Redis 连接
     *
     * @return Redis|mixed
     */
    protected function redis()
    {
        return di()->get(Redis::class);
    }

    /**
     * 获取缓存 KEY
     *
     * @param string|array $key
     * @return string
     */
    protected function getCacheKey($key = ''): string
    {
        $params = [$this->prefix, $this->name];
        if (is_array($key)) {
            $params = array_merge($params, $key);
        } else {
            $params[] = $key;
        }

        return $this->filter($params);
    }

    protected function filter(array $params = []): string
    {
        foreach ($params as $k => $param) {
            $params[$k] = trim((string)$param, ':');
        }

        return implode(':', array_filter($params));
    }
}
