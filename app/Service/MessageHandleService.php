<?php

namespace App\Service;

use App\Constants\SocketConstants;
use App\Constants\TalkMsgType;
use App\Constants\TalkType;
use Hyperf\Di\Annotation\Inject;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use App\Amqp\Producer\ChatMessageProducer;
use App\Model\Chat\TalkRecords;
use App\Model\Group\Group;
use App\Model\UsersFriend;
use App\Cache\LastMessage;
use App\Cache\UnreadTalk;

class MessageHandleService
{
    /**
     * @inject
     * @var SocketClientService
     */
    private $socketClientService;

    /**
     * 对话消息
     *
     * @param Response|Server $server
     * @param Frame           $frame
     * @param array|string    $data 解析后数据
     * @return void
     */
    public function onTalk($server, Frame $frame, $data)
    {
        $user_id = $this->socketClientService->findFdUserId($frame->fd);
        if ($user_id != $data['sender_id']) {
            return;
        }

        // 验证消息类型 私聊|群聊
        if (!in_array($data['talk_type'], TalkType::getTypes())) {
            return;
        }

        // 验证发送消息用户与接受消息用户之间是否存在好友或群聊关系(后期走缓存)
        if ($data['talk_type'] == TalkType::PRIVATE_CHAT) {
            // 判断发送者和接受者是否是好友关系
            if (!UsersFriend::isFriend((int)$data['sender_id'], (int)$data['receiver_id'], true)) {
                return;
            }
        } else if ($data['talk_type'] == TalkType::GROUP_CHAT) {
            // 判断是否属于群成员
            if (!Group::isMember((int)$data['receiver_id'], (int)$data['sender_id'])) {
                return;
            }
        }

        $result = TalkRecords::create([
            'talk_type'   => $data['talk_type'],
            'user_id'     => $data['sender_id'],
            'receiver_id' => $data['receiver_id'],
            'msg_type'    => TalkMsgType::TEXT_MESSAGE,
            'content'     => htmlspecialchars($data['text_message']),
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        if (!$result) return;

        // 判断是否私聊
        if ($result->talk_type == TalkType::PRIVATE_CHAT) {
            // 设置好友消息未读数
            UnreadTalk::getInstance()->increment($result->user_id, $result->receiver_id);
        }

        // 缓存最后一条聊天消息
        LastMessage::getInstance()->save($result->talk_type, $result->user_id, $result->receiver_id, [
            'text'       => mb_substr($result->content, 0, 30),
            'created_at' => $result->created_at
        ]);

        push_amqp(new ChatMessageProducer(SocketConstants::EVENT_TALK, [
            'sender_id'   => $result->user_id,
            'receiver_id' => $result->receiver_id,
            'talk_type'   => $result->talk_type,
            'record_id'   => $result->id
        ]));
    }

    /**
     * 键盘输入消息
     *
     * @param Response|Server $server
     * @param Frame           $frame
     * @param array|string    $data 解析后数据
     * @return false
     */
    public function onKeyboard($server, Frame $frame, $data)
    {
        push_amqp(new ChatMessageProducer(SocketConstants::EVENT_KEYBOARD, [
            'sender_id'   => intval($data['sender_id']),
            'receiver_id' => intval($data['receiver_id']),
        ]));
    }
}
