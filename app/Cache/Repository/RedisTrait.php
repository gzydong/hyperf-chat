<?php

namespace App\Cache\Repository;

use Hyperf\Redis\Redis;

trait RedisTrait
{
    private static $instance;

    /**
     * 获取单例
     *
     * @return static
     */
    static public function getInstance()
    {
        if (!(self::$instance instanceof static)) {
            self::$instance = new static();
        }

        return self::$instance;
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
     * @param string $key
     * @return string
     */
    private function getCacheKey(string $key)
    {
        return sprintf('%s:%s', trim($this->prefix, ':'), trim($key, ':'));
    }

    /**
     * 获取缓存 KEY
     *
     * @return string
     */
    protected function getKeyName()
    {
        return $this->getCacheKey($this->name);
    }

    /**
     * 加载数据到缓存
     */
    public function reload()
    {

    }
}
