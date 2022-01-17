<?php

namespace App\Service;

use App\Cache\FriendRemark;
use App\Model\Contact\Contact;

class UserFriendService
{
    /**
     * 获取好友备注
     *
     * @param int $user_id   用户ID
     * @param int $friend_id 好友ID
     * @return string
     */
    public function getFriendRemark(int $user_id, int $friend_id): string
    {
        $remark = FriendRemark::getInstance()->read($user_id, $friend_id);
        if ($remark) return $remark;

        $remark = Contact::where('user_id', $user_id)->where('friend_id', $friend_id)->value('remark');
        if ($remark) FriendRemark::getInstance()->save($user_id, $friend_id, $remark);

        return (string)$remark;
    }

    /**
     * 判断用户之间是否存在好友关系
     *
     * @param int  $user_id   用户ID
     * @param int  $friend_id 好友ID
     * @param bool $is_mutual 相互互为好友
     * @return bool
     */
    public function isFriend(int $user_id, int $friend_id, $is_mutual = false): bool
    {
        $isTrue1 = Contact::where('user_id', $user_id)->where('friend_id', $friend_id)->where('status', 1)->exists();

        if ($is_mutual === false) {
            return $isTrue1;
        }

        $isTrue2 = Contact::where('user_id', $friend_id)->where('friend_id', $user_id)->where('status', 1)->exists();

        return $isTrue1 && $isTrue2;
    }
}
