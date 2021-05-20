<?php

namespace App\Cache\Contracts;

interface ZSetRedisInterface
{
    public function add(string $member, float $score);

    public function rem(string ...$member);

    public function incr(string $member, float $score);

    public function isMember(string $member);

    public function count();

    public function all($asc = true, $is_score = true);

    public function rank($page = 1, $size = 10, $asc = true);

    public function getMemberRank(string $member, $asc = true);

    public function getMemberScore(string $member);

    public function delete();
}
