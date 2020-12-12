<?php

namespace App\Service;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Amqp\Producer;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use App\Amqp\Producer\ChatMessageProducer;
use App\Cache\LastMsgCache;
use App\Cache\UnreadTalkCache;
use App\Model\Chat\ChatRecord;
use App\Model\Group\UsersGroup;
use App\Model\UsersFriend;

class MessageHandleService
{
    /**
     * @Inject
     * @var Producer
     */
    private $producer;

    /**
     * @inject
     * @var SocketClientService
     */
    private $socketClientService;

    /**
     * @Inject
     * @var UnreadTalkCache
     */
    private $unreadTalkCache;

    /**
     * 对话消息
     *
     * @param Response|Server $server
     * @param Frame $frame
     * @param array|string $data 解析后数据
     * @return bool|void
     */
    public function onTalk($server, Frame $frame, $data)
    {
        $user_id = $this->socketClientService->findFdUserId($frame->fd);
        if ($user_id != $data['send_user']) {
            return;
        }

        //验证消息类型 私聊|群聊
        if (!in_array($data['source_type'], [1, 2])) {
            return;
        }

        //验证发送消息用户与接受消息用户之间是否存在好友或群聊关系(后期走缓存)
        if ($data['source_type'] == 1) {//私信
            //判断发送者和接受者是否是好友关系
            if (!UsersFriend::isFriend((int)$data['send_user'], (int)$data['receive_user'], true)) {
                return;
            }
        } else if ($data['source_type'] == 2) {//群聊
            //判断是否属于群成员
            if (!UsersGroup::isMember((int)$data['receive_user'], (int)$data['send_user'])) {
                return;
            }
        }

        $result = ChatRecord::create([
            'source' => $data['source_type'],
            'msg_type' => 1,
            'user_id' => $data['send_user'],
            'receive_id' => $data['receive_user'],
            'content' => htmlspecialchars($data['text_message']),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$result) return;

        // 判断是否私聊
        if ($data['source_type'] == 1) {
            // 设置好友消息未读数
            $this->unreadTalkCache->setInc(intval($result->receive_id), strval($result->user_id));
        }

        // 缓存最后一条消息
        LastMsgCache::set([
            'text' => mb_substr($result->content, 0, 30),
            'created_at' => $result->created_at
        ], (int)$data['receive_user'],
            $data['source_type'] == 1 ? (int)$data['send_user'] : 0
        );

        $this->producer->produce(
            new ChatMessageProducer('event_talk', [
                'sender' => intval($data['send_user']),  //发送者ID
                'receive' => intval($data['receive_user']),  //接收者ID
                'source' => intval($data['source_type']), //接收者类型 1:好友;2:群组
                'record_id' => $result->id
            ])
        );
    }

    /**
     * 键盘输入消息
     *
     * @param Response|Server $server
     * @param Frame $frame
     * @param array|string $data 解析后数据
     * @return bool|void
     */
    public function onKeyboard($server, Frame $frame, $data)
    {
        $this->producer->produce(
            new ChatMessageProducer('event_keyboard', [
                'send_user' => intval($data['send_user']),  //发送者ID
                'receive_user' => intval($data['receive_user']),  //接收者ID
            ])
        );
    }
}
