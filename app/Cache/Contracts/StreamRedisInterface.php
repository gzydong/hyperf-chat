<?php

namespace App\Cache\Contracts;

use Closure;

interface StreamRedisInterface
{
    public function add(array $messages, $maxLen = 0, $isApproximate = false);

    public function rem(string ...$id);

    public function ack(string $group, string $id);

    public function count();

    public function all();

    public function clear();

    public function delete();

    public function info(string $operation = 'stream');

    public function run(Closure $closure, string $group, string $consumer, $count = 1);
}
