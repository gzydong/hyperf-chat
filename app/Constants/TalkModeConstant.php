<?php

namespace App\Constants;

/**
 * 聊天对话模式
 *
 * @package App\Constants
 */
class TalkModeConstant
{
    /**
     * 私信
     */
    const PRIVATE_CHAT = 1;

    /**
     * 群聊
     */
    const GROUP_CHAT = 2;

    public static function getTypes(): array
    {
        return [
            self::PRIVATE_CHAT,
            self::GROUP_CHAT
        ];
    }
}
