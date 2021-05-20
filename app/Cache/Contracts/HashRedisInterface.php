<?php

namespace App\Cache\Contracts;

interface HashRedisInterface
{
    public function get(string ...$key);

    public function add(string $key, string $value);

    public function rem(string ...$key);

    public function incr(string $member, int $score);

    public function count();

    public function all();

    public function isMember(string $key);

    public function delete();
}

