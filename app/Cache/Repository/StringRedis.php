<?php

namespace App\Cache\Repository;

use App\Traits\StaticInstance;
use App\Cache\Contracts\StringRedisInterface;

/**
 * String Cache
 *
 * @package App\Cache\Repository
 */
class StringRedis extends AbstractRedis implements StringRedisInterface
{
    protected $prefix = 'rds-string';

    protected $name = '';

    /**
     * 设置缓存
     *
     * @param string $key     缓存标识
     * @param string $value   缓存数据
     * @param null   $expires 过期时间
     * @return bool
     */
    public function set(string $key, string $value, $expires = null)
    {
        return $this->redis()->set($this->getCacheKey($key), $value, $expires);
    }

    /**
     * 获取缓存数据
     *
     * @param string $key 缓存标识
     * @return false|mixed|string
     */
    public function get(string $key)
    {
        return $this->redis()->get($this->getCacheKey($key));
    }

    /**
     * 删除 String 缓存
     *
     * @param string $key 缓存标识
     * @return bool
     */
    public function delete(string $key)
    {
        return (bool)$this->redis()->del($key);
    }

    /**
     * 判断缓存是否存在
     *
     * @param string $key 缓存标识
     * @return bool
     */
    public function isExist(string $key)
    {
        return (bool)$this->get($key);
    }

    /**
     * 获取缓存过期时间
     *
     * @param string $key 缓存标识
     * @return bool|int
     */
    public function ttl(string $key)
    {
        return $this->redis()->ttl($this->getCacheKey($key));
    }
}
