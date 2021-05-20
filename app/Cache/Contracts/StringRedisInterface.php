<?php

namespace App\Cache\Contracts;

interface StringRedisInterface
{
    public function set(string $key, string $value, $expires = null);

    public function get(string $key);

    public function delete(string $key);

    public function isExist(string $key);

    public function ttl(string $key);
}
