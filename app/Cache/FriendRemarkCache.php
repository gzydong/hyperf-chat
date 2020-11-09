<?php

namespace App\Cache;

/**
 * Class FriendRemarkCache
 * @package App\Cache
 */
class FriendRemarkCache
{
    const KEY = 'hash:user:friend:remark:cache';

    /**
     * 设置好友备注缓存
     *
     * @param int $user_id 用户ID
     * @param int $friend_id 好友ID
     * @param string $remark 好友备注
     */
    public static function set(int $user_id, int $friend_id, string $remark)
    {
        redis()->hset(self::KEY, "{$user_id}_{$friend_id}", $remark);
    }

    /**
     * 获取好友备注
     *
     * @param int $user_id 用户ID
     * @param int $friend_id 好友ID
     * @return string
     */
    public static function get(int $user_id, int $friend_id)
    {
        return redis()->hget(self::KEY, "{$user_id}_{$friend_id}") ?: '';
    }
}
