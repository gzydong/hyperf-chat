<?php

namespace App\Cache\Repository;

class HashGroupRedis extends AbstractRedis
{
    protected $prefix = 'rds-hash';

    protected $name = 'default';

    /**
     * @param string $name
     * @param string $key
     * @param        $value
     * @return bool|int
     */
    public function add(string $name, string $key, $value)
    {
        return $this->redis()->hSet($this->getCacheKey($name), $key, $value);
    }

    /**
     * @param string     $name
     * @param string|int $key
     * @return false|string
     */
    public function get(string $name, string $key)
    {
        return $this->redis()->hGet($this->getCacheKey($name), $key);
    }

    /**
     * @param string $name
     * @return array
     */
    public function getAll(string $name)
    {
        return $this->redis()->hGetAll($this->getCacheKey($name));
    }

    /**
     * @param string $name
     * @param string $key
     * @return bool|int
     */
    public function rem(string $name, string $key)
    {
        return $this->redis()->hDel($this->getCacheKey($name), $key);
    }

    /**
     * @param string $name
     * @param string $key
     * @param int    $value
     * @return int
     */
    public function incr(string $name, string $key, int $value = 1)
    {
        return $this->redis()->hIncrBy($this->getCacheKey($name), $key, $value);
    }

    /**
     * @param string $name
     * @param string $key
     * @return bool
     */
    public function isMember(string $name, string $key)
    {
        return $this->redis()->hExists($this->getCacheKey($name), $key);
    }

    /**
     * @param string $name
     * @return false|int
     */
    public function count(string $name)
    {
        return $this->redis()->hLen($this->getCacheKey($name));
    }

    public function delete(string $name)
    {
        return $this->redis()->del($this->getCacheKey($name));
    }
}
