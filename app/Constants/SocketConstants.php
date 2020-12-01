<?php

namespace App\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

/**
 * WebSocket 消息事件
 *
 * @Constants
 */
class SocketConstants extends AbstractConstants
{
    /**
     * @Message("对话消息通知 - 事件名)
     */
    const EVENT_TALK = 'event_talk';

    /**
     * @Message("键盘输入事件通知 - 事件名")
     */
    const EVENT_KEYBOARD = 'event_keyboard';

    /**
     * @Message("用户在线状态通知 - 事件名")
     */
    const EVENT_ONLINE_STATUS = 'event_online_status';

    /**
     * @Message("聊天消息撤销通知 - 事件名")
     */
    const EVENT_REVOKE_TALK = 'event_revoke_talk';

    /**
     * @Message("好友申请消息通知 - 事件名")
     */
    const EVENT_FRIEND_APPLY = 'event_friend_apply';

    /**
     * @Message("WebSocket 消息消费队列交换机名称")
     */
    const CONSUMER_MESSAGE_EXCHANGE = 'im.message.fanout';
}
