<?php
declare(strict_types=1);

namespace App\Service\Message;

use App\Constant\TalkEventConstant;
use App\Constant\TalkModeConstant;
use App\Event\TalkEvent;
use App\Service\SocketClientService;
use App\Service\TalkMessageService;
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
        TalkEventConstant::EVENT_TALK          => 'onTalk',
        TalkEventConstant::EVENT_TALK_KEYBOARD => 'onKeyboard',
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
        if (!in_array($data['talk_type'], TalkModeConstant::getTypes())) return;

        // 验证发送消息用户与接受消息用户之间是否存在好友或群聊关系
        $isTrue = UserRelation::isFriendOrGroupMember($user_id, (int)$data['receiver_id'], (int)$data['talk_type']);
        if (!$isTrue) {
            $server->push($frame->fd, json_encode(['event_error', [
                'message' => '暂不属于好友关系或群聊成员，无法发送聊天消息！'
            ]]));
            return;
        }

        di()->get(TalkMessageService::class)->insertText([
            'talk_type'   => $data['talk_type'],
            'user_id'     => $data['sender_id'],
            'receiver_id' => $data['receiver_id'],
            'content'     => $data['text_message'],
        ]);
    }

    /**
     * 键盘输入消息
     *
     * @param Response|Server $server
     * @param Frame           $frame
     * @param array|string    $data 解析后数据
     * @return void
     */
    public function onKeyboard($server, Frame $frame, $data)
    {
        event()->dispatch(new TalkEvent(TalkEventConstant::EVENT_TALK_KEYBOARD, [
            'sender_id'   => (int)$data['sender_id'],
            'receiver_id' => (int)$data['receiver_id'],
        ]));
    }
}
