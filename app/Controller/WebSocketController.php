<?php
declare(strict_types=1);
/**
 *
 * This is my open source code, please do not use it for commercial applications.
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author Yuandong<837215079@qq.com>
 * @link   https://github.com/gzydong/hyperf-chat
 */

namespace App\Controller;

use Hyperf\Di\Annotation\Inject;
use App\Constants\SocketConstants;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\Amqp\Producer;
use Swoole\Http\Request;
use Swoole\Websocket\Frame;
use Swoole\Http\Response;
use Swoole\WebSocket\Server;
use Phper666\JWTAuth\JWT;
use App\Service\SocketClientService;
use App\Service\MessageHandleService;
use App\Service\SocketRoomService;
use App\Model\Group\UsersGroupMember;
use App\Amqp\Producer\ChatMessageProducer;
use App\Support\SocketIOParser;

/**
 * Class WebSocketController
 * @package App\Controller
 */
class WebSocketController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
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
     * @var SocketClientService
     */
    private $socketClientService;

    /**
     * @inject
     * @var SocketRoomService
     */
    private $socketRoomService;

    /**
     * @inject
     * @var MessageHandleService
     */
    private $messageHandleService;

    /**
     * 消息事件绑定
     */
    const EVENTS = [
        SocketConstants::EVENT_TALK => 'onTalk',
        SocketConstants::EVENT_KEYBOARD => 'onKeyboard',
    ];

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

        // 判断是否存在异地登录
        $isOnline = $this->socketClientService->isOnlineAll(intval($userInfo['user_id']));

        // 若开启单点登录，则主动关闭之前登录的连接
        if ($isOnline) {
            // ... 预留
        }

        // 绑定fd与用户关系
        $this->socketClientService->bindRelation($request->fd, $userInfo['user_id']);

        // 加入群聊
        $groupIds = UsersGroupMember::getUserGroupIds($userInfo['user_id']);
        foreach ($groupIds as $group_id) {
            $this->socketRoomService->addRoomMember($userInfo['user_id'], $group_id);
        }

        if (!$isOnline) {
            // 推送消息至队列
            $this->producer->produce(
                new ChatMessageProducer(SocketConstants::EVENT_ONLINE_STATUS, [
                    'user_id' => $userInfo['user_id'],
                    'status' => 1,
                    'notify' => '好友上线通知...'
                ])
            );
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

        //$result = SocketIOParser::decode($frame->data);
        $result = json_decode($frame->data, true);
        if (!isset(self::EVENTS[$result['event']])) {
            return;
        }

        // 回调事件处理函数
        call_user_func_array([
            $this->messageHandleService,
            self::EVENTS[$result['event']]
        ], [
            $server,
            $frame,
            $result['data']
        ]);
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
        $user_id = $this->socketClientService->findFdUserId($fd);

        stdout_log()->notice("客户端FD:{$fd} 已关闭连接 ，用户ID为【{$user_id}】，关闭时间：" . date('Y-m-d H:i:s'));

        // 解除fd关系
        $this->socketClientService->removeRelation($fd);

        // 判断是否存在异地登录
        $isOnline = $this->socketClientService->isOnlineAll(intval($user_id));
        if (!$isOnline) {
            // 推送消息至队列
            $this->producer->produce(
                new ChatMessageProducer(SocketConstants::EVENT_ONLINE_STATUS, [
                    'user_id' => $user_id,
                    'status' => 0,
                    'notify' => '好友离线通知通知...'
                ])
            );
        }
    }
}
