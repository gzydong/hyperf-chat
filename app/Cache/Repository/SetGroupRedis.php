<?php

namespace App\Cache\Repository;

class SetGroupRedis extends AbstractRedis
{
    protected $prefix = 'rds-set';

    protected $name = 'default';

    /**
     * 添加成员
     *
     * @param string     $name      分组名
     * @param string|int ...$member 分组成员
     * @return bool|int
     */
    public function add(string $name, ...$member)
    {
        return $this->redis()->sAdd($this->getCacheKey($name), ...$member);
    }

    /**
     * 删除成员
     *
     * @param string     $name      分组名
     * @param string|int ...$member 分组成员
     * @return int
     */
    public function rem(string $name, ...$member)
    {
        return $this->redis()->sRem($this->getCacheKey($name), ...$member);
    }

    /**
     * 判断成员是否存在
     *
     * @param string $name 分组名
     * @param string $key  分组成员
     * @return bool
     */
    public function isMember(string $name, string $key)
    {
        return $this->redis()->sIsMember($this->getCacheKey($name), $key);
    }

    /**
     * 获取分组中所有成员
     *
     * @param string $name 分组名
     * @return array
     */
    public function all(string $name)
    {
        return $this->redis()->sMembers($this->getCacheKey($name));
    }

    /**
     * 获取分组中成员数量
     *
     * @param string $name 分组名
     * @return int
     */
    public function count(string $name)
    {
        return $this->redis()->sCard($this->getCacheKey($name));
    }

    /**
     * 删除分组
     *
     * @param string $name 分组名
     * @return int
     */
    public function delete(string $name)
    {
        return $this->redis()->del($this->getCacheKey($name));
    }

    /**
     * 获取随机集合中的元素
     *
     * @param int $count
     * @return array|bool|mixed|string
     */
    public function randMember(string $name, $count = 1)
    {
        return $this->redis()->sRandMember($this->getCacheKey($name), $count);
    }
}
