<?php

namespace App\Cache\Repository;

use App\Traits\StaticInstance;
use Hyperf\Redis\Redis;

abstract class AbstractRedis
{
    use StaticInstance;

    protected $prefix = 'rds';

    protected $name = '';

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
     * @param string $key
     * @return string
     */
    protected function getCacheKey($key = '')
    {
        return implode(':', array_filter([
            trim($this->prefix, ':'),
            trim($this->name, ':'),
            trim($key, ':')
        ]));
    }
}
