<?php
declare(strict_types=1);

namespace App\Constant;

/**
 * WebSocket 消息事件枚举
 *
 * @package App\Constants
 */
class TalkEventConstant
{
    /**
     * 对话消息通知 - 事件名
     */
    const EVENT_TALK = 'event_talk';

    /**
     * 键盘输入事件通知 - 事件名
     */
    const EVENT_TALK_KEYBOARD = 'event_talk_keyboard';

    /**
     * 用户在线状态通知 - 事件名
     */
    const EVENT_LOGIN = 'event_login';

    /**
     * 聊天消息撤销通知 - 事件名
     */
    const EVENT_TALK_REVOKE = 'event_talk_revoke';

    /**
     * 好友申请消息通知 - 事件名
     */
    const EVENT_CONTACT_APPLY = 'event_contact_apply';

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::EVENT_TALK          => '对话消息通知',
            self::EVENT_TALK_KEYBOARD => '键盘输入事件通知',
            self::EVENT_LOGIN         => '用户在线状态通知',
            self::EVENT_TALK_REVOKE   => '聊天消息撤销通知',
            self::EVENT_CONTACT_APPLY => '好友申请消息通知'
        ];
    }
}
