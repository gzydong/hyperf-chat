<?php

namespace App\Service;

use App\Cache\FriendRemark;
use App\Model\UsersFriend;

class UserFriendService
{
    /**
     * 获取好友备注
     *
     * @param int $user_id   用户ID
     * @param int $friend_id 好友ID
     * @return string
     */
    public function getFriendRemark(int $user_id, int $friend_id)
    {
        $remark = FriendRemark::getInstance()->read($user_id, $friend_id);
        if ($remark) return $remark;

        $remark = UsersFriend::where('user_id', $user_id)->where('friend_id', $friend_id)->value('remark');
        if ($remark) FriendRemark::getInstance()->save($user_id, $friend_id, $remark);

        return (string)$remark;
    }

    /**
     * 判断用户之间是否存在好友关系
     *
     * @param int  $user_id   用户ID
     * @param int  $friend_id 好友ID
     * @param bool $is_cache  是否允许读取缓存
     * @param bool $is_mutual 相互互为好友
     * @return bool
     */
    public function isFriend(int $user_id, int $friend_id, bool $is_cache = false, $is_mutual = false)
    {
        $cacheKey = "good_friends:{$user_id}_{$friend_id}";
        if ($is_cache && redis()->get($cacheKey)) {
            return true;
        }

        $isTrue = UsersFriend::query()->where('user_id', $user_id)->where('friend_id', $friend_id)->where('status', 1)->exists();
        if ($isTrue) {
            redis()->setex($cacheKey, 60 * 5, 1);
        }

        return $isTrue;
    }
}
