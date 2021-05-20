<?php

namespace App\Cache\Repository;

use App\Cache\Contracts\ListRedisInterface;

/**
 * Redis List
 *
 * @package App\Cache\Repository
 */
class ListRedis implements ListRedisInterface
{
    use RedisTrait;

    private $prefix = 'rds:list';

    private $name = 'default';

    /**
     * Push 队列任务
     *
     * @param string ...$value
     * @return false|int
     */
    public function push(string ...$value)
    {
        return $this->redis()->lPush($this->getKeyName(), ...$value);
    }

    /**
     * 获取队列中的任务
     *
     * @return bool|mixed
     */
    public function pop()
    {
        return $this->redis()->rPop($this->getKeyName());
    }

    /**
     * 获取列表中元素总数
     *
     * @return int
     */
    public function count()
    {
        return (int)$this->redis()->lLen($this->getKeyName());
    }

    /**
     * 清除列表中所有元素
     *
     * @return boolean
     */
    public function clear()
    {
        return $this->redis()->lTrim($this->getKeyName(), 1, 0);
    }

    /**
     * 获取列表中所有元素
     *
     * @return array
     */
    public function all()
    {
        return $this->redis()->lRange($this->getKeyName(), 0, -1);
    }

    /**
     * 删除 List
     *
     * @return bool
     */
    public function delete()
    {
        return (bool)$this->redis()->del($this->getKeyName());
    }
}
