<?php

namespace App\Constants;

/**
 * Class TalkMessageType
 *
 * @package App\Constants
 */
class TalkMessageType
{
    const TEXT_MESSAGE         = 1;//文本消息
    const FILE_MESSAGE         = 2;//文件消息
    const FORWARD_MESSAGE      = 3;//会话消息
    const CODE_MESSAGE         = 4;//代码消息
    const VOTE_MESSAGE         = 5;//投票消息
    const GROUP_NOTICE_MESSAGE = 6;//群公告
    const FRIEND_APPLY_MESSAGE = 7;//好友申请
    const USER_LOGIN           = 8;//登录通知
}
