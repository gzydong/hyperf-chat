<?php
declare(strict_types=1);

namespace App\Controller;

use App\Cache\LastMsgCache;
use App\Cache\UnreadTalkCache;
use App\Model\Chat\ChatRecord;
use App\Model\Group\UsersGroupMember;
use App\Service\SocketRoomService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Phper666\JWTAuth\JWT;
use Swoole\Http\Request;
use Swoole\Websocket\Frame;
use Hyperf\Amqp\Producer;
use App\Amqp\Producer\ChatMessageProducer;
use Swoole\Http\Response;
use Swoole\WebSocket\Server;
use App\Traits\WebSocketTrait;
use App\Service\SocketFDService;

/**
 * Class WebSocketController
 * @package App\Controller
 */
class WebSocketController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    use WebSocketTrait;

    /**
     * @Inject
     * @var JWT
     */
    private $jwt;

    /**
     * @Inject
     * @var Producer
     */
    private $producer;

    /**
     * @inject
     * @var SocketFDService
     */
    private $socketFDService;

    /**
     * @inject
     * @var SocketRoomService
     */
    private $socketRoomService;

    /**
     * 连接创建成功回调事件
     *
     * @param Response|Server $server
     * @param Request $request
     */
    public function onOpen($server, Request $request): void
    {
        $token = $request->get['token'] ?? '';
        $userInfo = $this->jwt->getParserData($token);
        stdout_log()->notice("用户连接信息 : user_id:{$userInfo['user_id']} | fd:{$request->fd} 时间：" . date('Y-m-d H:i:s'));

        // 绑定fd与用户关系
        $this->socketFDService->bindRelation($request->fd, $userInfo['user_id']);

        // 加入群聊
        $groupIds = UsersGroupMember::getUserGroupIds($userInfo['user_id']);
        foreach ($groupIds as $group_id) {
            $this->socketRoomService->addRoomMember($userInfo['user_id'], $group_id);
        }
    }

    /**
     * 消息接收回调事件
     *
     * @param Response|Server $server
     * @param Frame $frame
     */
    public function onMessage($server, Frame $frame): void
    {
        // 判断是否为心跳检测
        if ($frame->data == 'PING') return;

        // 当前用户ID
        $user_id = $this->socketFDService->findFdUserId($frame->fd);

        [$event, $data] = array_values(json_decode($frame->data, true));
        if ($user_id != $data['send_user']) {
            return;
        }

        //验证消息类型 私聊|群聊
        if (!in_array($data['source_type'], [1, 2])) {
            return;
        }

        //验证发送消息用户与接受消息用户之间是否存在好友或群聊关系(后期走缓存)
//        if ($data['source_type'] == 1) {//私信
//            //判断发送者和接受者是否是好友关系
//            if (!UsersFriend::isFriend(intval($data['send_user']), intval($data['receive_user']))) {
//                return;
//            }
//        } else if ($data['source_type'] == 2) {//群聊
//            //判断是否属于群成员
//            if (!UsersGroup::isMember(intval($data['receive_user']), intval($data['send_user']))) {
//                return;
//            }
//        }

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
            $msg_text = mb_substr($result->content, 0, 30);
            // 缓存最后一条消息
            LastMsgCache::set([
                'text' => $msg_text,
                'created_at' => $result->created_at
            ], intval($data['receive_user']), intval($data['send_user']));

            // 设置好友消息未读数
            make(UnreadTalkCache::class)->setInc(intval($result->receive_id), strval($result->user_id));
        }

        $this->producer->produce(
            new ChatMessageProducer($data['send_user'], $data['receive_user'], $data['source_type'], $result->id)
        );
    }

    /**
     * 连接创建成功回调事件
     *
     * @param Response|\Swoole\Server $server
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose($server, int $fd, int $reactorId): void
    {
        $user_id = $this->socketFDService->findFdUserId($fd);

        stdout_log()->notice("客户端FD:{$fd} 已关闭连接 ，用户ID为【{$user_id}】，关闭时间：" . date('Y-m-d H:i:s'));

        // 解除fd关系
        $this->socketFDService->removeRelation($fd);

        // 判断是否存在异地登录
        $isOnline = $this->socketFDService->isOnlineAll(intval($user_id));
        if (!$isOnline) {
            // ... 不存在异地登录，推送下线通知消息
            // ... 包装推送消息至队列
        } else {
            stdout_log()->notice("用户:{$user_id} 存在异地登录...");
        }
    }
}
