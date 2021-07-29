<?php

namespace App\Cache;

use App\Cache\Repository\HashRedis;
use App\Traits\StaticInstance;

/**
 * 好友申请未读数 - 缓存助手
 *
 * @package App\Cache
 */
class FriendApply extends HashRedis
{
    use StaticInstance;

    public $name = 'friend-apply';
}
