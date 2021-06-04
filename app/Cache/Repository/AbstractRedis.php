<?php

namespace App\Cache\Repository;

use Hyperf\Redis\Redis;

abstract class AbstractRedis
{
    protected $prefix = 'rds';

    protected $name = '';

    public static function getInstance()
    {
        return container()->get(static::class);
    }

    /**
     * 获取 Redis 连接
     *
     * @return Redis|mixed
     */
    protected function redis()
    {
        return redis();
    }

    /**
     * 获取缓存 KEY
     *
     * @param string|array $key
     * @return string
     */
    protected function getCacheKey($key = '')
    {
        $params = [$this->prefix, $this->name];
        if (is_array($key)) {
            $params = array_merge($params, $key);
        } else {
            $params[] = $key;
        }

        return $this->filter($params);
    }

    protected function filter(array $params = [])
    {
        foreach ($params as $k => $param) {
            $params[$k] = trim($param, ':');
        }

        return implode(':', array_filter($params));
    }
}
