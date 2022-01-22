<?php
declare(strict_types=1);

namespace App\Constant;

/**
 * Class TalkMessageType
 *
 * @package App\Constants
 */
class TalkMessageType
{
    const SYSTEM_TEXT_MESSAGE  = 0;  //系统文本消息
    const TEXT_MESSAGE         = 1;  //文本消息
    const FILE_MESSAGE         = 2;  //文件消息
    const FORWARD_MESSAGE      = 3;  //会话消息
    const CODE_MESSAGE         = 4;  //代码消息
    const VOTE_MESSAGE         = 5;  //投票消息
    const GROUP_NOTICE_MESSAGE = 6;  //群组公告
    const FRIEND_APPLY_MESSAGE = 7;  //好友申请
    const USER_LOGIN_MESSAGE   = 8;  //登录通知
    const GROUP_INVITE_MESSAGE = 9;  //入群退群消息
    const LOCATION_MESSAGE     = 10; //位置消息(预留)

    /**
     * 获取可转发的消息类型列表
     *
     * @return array
     */
    public static function getForwardTypes(): array
    {
        return [
            self::TEXT_MESSAGE,
            self::FILE_MESSAGE,
            self::CODE_MESSAGE
        ];
    }
}
