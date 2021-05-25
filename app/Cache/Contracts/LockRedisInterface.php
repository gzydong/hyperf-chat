<?php

namespace App\Cache\Contracts;

interface LockRedisInterface
{
    public function lock(string $key, $expired = 1, int $timeout = 0);

    public function delete(string $key);
}
