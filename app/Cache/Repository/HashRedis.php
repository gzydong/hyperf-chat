<?php

namespace App\Cache\Repository;

use App\Cache\Contracts\HashRedisInterface;

/**
 * Redis Hash
 *
 * @package App\Cache\Repository
 */
class HashRedis implements HashRedisInterface
{
    use RedisTrait;

    private $prefix = 'rds:hash';

    private $name = 'default';

    /**
     * 获取 Hash 值
     *
     * @param string ...$key
     * @return array|string
     */
    public function get(string ...$key)
    {
        $func = function ($k) {
            return (string)$this->redis()->hGet($this->getKeyName(), $k);
        };

        if (func_num_args() == 1) return $func($key[0]);

        $array = [];
        foreach ($key as $arg) {
            $array[$arg] = $func($arg);
        }

        return $array;
    }

    /**
     * 设置 Hash 值
     *
     * @param string $key
     * @param string $value
     */
    public function add(string $key, string $value)
    {
        $this->redis()->hSet($this->getKeyName(), $key, $value);
    }

    /**
     * 删除 hash 值
     *
     * @param string ...$key
     * @return bool|int
     */
    public function rem(string ...$key)
    {
        return $this->redis()->hDel($this->getKeyName(), ...$key);
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
        return $this->redis()->hincrby($this->getKeyName(), $member, $score);
    }

    /**
     * 获取 Hash 中元素总数
     *
     * @return int
     */
    public function count()
    {
        return (int)$this->redis()->hLen($this->getKeyName());
    }

    /**
     * 获取 Hash 中所有元素
     *
     * @return array
     */
    public function all()
    {
        return $this->redis()->hGetAll($this->getKeyName());
    }

    /**
     * 判断 hash 表中是否存在某个值
     *
     * @param string $key
     * @return bool
     */
    public function isMember(string $key)
    {
        return $this->redis()->hExists($this->getKeyName(), $key);
    }

    /**
     * 删除 Hash 表
     *
     * @return bool
     */
    public function delete()
    {
        return (bool)$this->redis()->del($this->getKeyName());
    }
}
