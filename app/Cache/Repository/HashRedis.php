<?php

namespace App\Cache\Repository;

use App\Cache\Contracts\HashRedisInterface;

/**
 * Redis Hash
 *
 * @package App\Cache\Repository
 */
class HashRedis extends AbstractRedis implements HashRedisInterface
{
    protected $prefix = 'rds-hash';

    protected $name = 'default';

    /**
     * 获取 Hash 值
     *
     * @param string ...$key
     * @return array|string
     */
    public function get(string ...$key)
    {
        if (func_num_args() == 1) {
            return (string)$this->redis()->hGet($this->getCacheKey(), $key[0]);
        }

        return $this->redis()->hMGet($this->getCacheKey(), $key);
    }

    /**
     * 设置 Hash 值
     *
     * @param string     $key
     * @param string|int $value
     */
    public function add(string $key, $value)
    {
        $this->redis()->hSet($this->getCacheKey(), $key, $value);
    }

    /**
     * 删除 hash 值
     *
     * @param string ...$key
     * @return bool|int
     */
    public function rem(string ...$key)
    {
        return $this->redis()->hDel($this->getCacheKey(), ...$key);
    }

    /**
     * 给指定元素累加值
     *
     * @param string $member 元素
     * @param int    $score
     * @return float
     */
    public function incr(string $member, int $score)
    {
        return $this->redis()->hincrby($this->getCacheKey(), $member, $score);
    }

    /**
     * 获取 Hash 中元素总数
     *
     * @return int
     */
    public function count()
    {
        return (int)$this->redis()->hLen($this->getCacheKey());
    }

    /**
     * 获取 Hash 中所有元素
     *
     * @return array
     */
    public function all()
    {
        return $this->redis()->hGetAll($this->getCacheKey());
    }

    /**
     * 判断 hash 表中是否存在某个值
     *
     * @param string $key
     * @return bool
     */
    public function isMember(string $key)
    {
        return $this->redis()->hExists($this->getCacheKey(), $key);
    }

    /**
     * 删除 Hash 表
     *
     * @return bool
     */
    public function delete()
    {
        return (bool)$this->redis()->del($this->getCacheKey());
    }
}
