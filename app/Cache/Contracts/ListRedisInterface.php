<?php

namespace App\Cache\Contracts;

interface ListRedisInterface
{
    public function push(string ...$value);

    public function pop();

    public function count();

    public function clear();

    public function all();

    public function delete();
}
