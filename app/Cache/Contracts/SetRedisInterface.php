<?php

namespace App\Cache\Contracts;

interface SetRedisInterface
{
    public function count();

    public function add(string ...$member);

    public function rem(string ...$member);

    public function isMember(string $member);

    public function randMember($count = 1);

    public function all();

    public function delete();
}
