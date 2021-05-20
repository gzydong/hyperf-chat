<?php

namespace App\Cache\Contracts;

interface LockRedisInterface
{
    public function lock(string $key, $lockTime = 1, $timeout = 0);

    public function delete(string $key);
}
