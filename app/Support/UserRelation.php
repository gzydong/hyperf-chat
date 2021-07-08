<?php

namespace App\Support;

use App\Constants\TalkMode;
use App\Model\Group\Group;
use App\Model\UsersFriend;

class UserRelation
{
    /**
     * 判断是否是好友或者群成员关系
     *
     * @param int $user_id     用户ID
     * @param int $receiver_id 接收者ID
     * @param int $talk_type   对话类型
     * @return bool
     */
    public static function isFriendOrGroupMember(int $user_id, int $receiver_id, int $talk_type)
    {
        if ($talk_type == TalkMode::PRIVATE_CHAT) {
            return UsersFriend::isFriend($user_id, $receiver_id, true);
        } else if ($talk_type == TalkMode::GROUP_CHAT) {
            return Group::isMember($receiver_id, $user_id);
        }

        return false;
    }
}
