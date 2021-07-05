<?php

namespace App\Constants;

class TalkType
{
    /**
     * 私聊
     */
    const PRIVATE_CHAT = 1;

    /**
     * 群聊
     */
    const GROUP_CHAT = 2;


    public static function getTypes()
    {
        return [
            self::PRIVATE_CHAT,
            self::GROUP_CHAT
        ];
    }
}
