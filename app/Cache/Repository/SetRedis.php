<?php

namespace App\Cache\Repository;

use App\Traits\StaticInstance;
use App\Cache\Contracts\SetRedisInterface;

/**
 * Redis Set
 *
 * @package App\Cache\Repository
 */
class SetRedis extends AbstractRedis implements SetRedisInterface
{
    protected $prefix = 'rds-set';

    protected $name = 'default';

    /**
     * 添加集合元素
     *
     * @param string ...$member
     * @return bool|int
     */
    public function add(string ...$member)
    {
        return $this->redis()->sAdd($this->getCacheKey(), ...$member);
    }

    /**
     * 删除集合元素
     *
     * @param string ...$member
     * @return int
     */
    public function rem(string ...$member)
    {
        return $this->redis()->sRem($this->getCacheKey(), ...$member);
    }

    /**
     * 判断是否是集合元素
     *
     * @param string $member
     * @return bool
     */
    public function isMember(string $member)
    {
        return $this->redis()->sIsMember($this->getCacheKey(), $member);
    }

    /**
     * 获取集合中所有元素
     *
     * @return array
     */
    public function all()
    {
        return $this->redis()->sMembers($this->getCacheKey());
    }

    /**
     * 获取集合中元素个数
     *
     * @return int
     */
    public function count()
    {
        return $this->redis()->scard($this->getCacheKey());
    }

    /**
     * 获取随机集合中的元素
     *
     * @param int $count
     * @return array|bool|mixed|string
     */
    public function randMember($count = 1)
    {
        return $this->redis()->sRandMember($this->getCacheKey(), $count);
    }

    /**
     * 删除 Set 集合表
     *
     * @return bool
     */
    public function delete()
    {
        return (bool)$this->redis()->del($this->getCacheKey());
    }
}
