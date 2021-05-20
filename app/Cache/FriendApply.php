<?php

namespace App\Cache;

use App\Cache\Repository\HashRedis;

/**
 * 好友申请未读数 - 缓存助手
 *
 * @package App\Cache
 */
class FriendApply extends HashRedis
{
    public $name = 'friend-apply';
}
