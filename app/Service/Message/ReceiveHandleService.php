<?php

namespace App\Service\Message;

use App\Cache\LastMessage;
use App\Cache\UnreadTalk;
use App\Constants\TalkMessageEvent;
use App\Constants\TalkMessageType;
use App\Constants\TalkMode;
use App\Model\Talk\TalkRecords;
use App\Model\Group\Group;
use App\Model\UsersFriend;
use App\Service\SocketClientService;
use App\Service\UserFriendService;
use App\Support\MessageProducer;
use App\Support\UserRelation;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class ReceiveHandleService
{
    /**
     * @var SocketClientService
     */
    private $client;

    // 消息事件绑定
    const EVENTS = [
        TalkMessageEvent::EVENT_TALK     => 'onTalk',
        TalkMessageEvent::EVENT_KEYBOARD => 'onKeyboard',
    ];

    /**
     * ReceiveHandleService constructor.
     *
     * @param SocketClientService $client
     */
    public function __construct(SocketClientService $client)
    {
        $this->client = $client;
    }

    /**
     * 对话文本消息
     *
     * @param Response|Server $server
     * @param Frame           $frame
     * @param array|string    $data 解析后数据
     * @return void
     */
    public function onTalk($server, Frame $frame, $data)
    {
        $user_id = $this->client->findFdUserId($frame->fd);
        if ($user_id != $data['sender_id']) return;

        // 验证消息类型
        if (!in_array($data['talk_type'], TalkMode::getTypes())) return;

        // 验证发送消息用户与接受消息用户之间是否存在好友或群聊关系
        $isTrue = UserRelation::isFriendOrGroupMember($user_id, (int)$data['receiver_id'], (int)$data['talk_type']);
        if (!$isTrue) {
            $server->push($frame->fd, json_encode(['event_error', [
                'message' => '暂不属于好友关系或群聊成员，无法发送聊天消息！'
            ]]));
            return;
        }

        $result = TalkRecords::create([
            'talk_type'   => $data['talk_type'],
            'user_id'     => $data['sender_id'],
            'receiver_id' => $data['receiver_id'],
            'msg_type'    => TalkMessageType::TEXT_MESSAGE,
            'content'     => htmlspecialchars($data['text_message']),
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        // 判断是否私信
        if ($result->talk_type == TalkMode::PRIVATE_CHAT) {
            UnreadTalk::getInstance()->increment($result->user_id, $result->receiver_id);
        }

        // 缓存最后一条聊天消息
        LastMessage::getInstance()->save($result->talk_type, $result->user_id, $result->receiver_id, [
            'text'       => mb_substr($result->content, 0, 30),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        MessageProducer::publish(MessageProducer::create(TalkMessageEvent::EVENT_TALK, [
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
        MessageProducer::publish(MessageProducer::create(TalkMessageEvent::EVENT_KEYBOARD, [
            'sender_id'   => (int)$data['sender_id'],
            'receiver_id' => (int)$data['receiver_id'],
        ]));
    }
}
