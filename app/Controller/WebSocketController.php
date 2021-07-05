<?php
declare(strict_types=1);
/**
 * This is my open source code, please do not use it for commercial applications.
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author Yuandong<837215079@qq.com>
 * @link   https://github.com/gzydong/hyperf-chat
 */

namespace App\Controller;

use App\Cache\SocketRoom;
use Hyperf\Di\Annotation\Inject;
use App\Constants\SocketConstants;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Swoole\Http\Request;
use Swoole\Websocket\Frame;
use Swoole\Http\Response;
use Swoole\WebSocket\Server;
use App\Service\SocketClientService;
use App\Service\MessageHandleService;
use App\Model\Group\GroupMember;
use App\Amqp\Producer\ChatMessageProducer;

/**
 * Class WebSocketController
 *
 * @package App\Controller
 */
class WebSocketController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    /**
     * @inject
     * @var SocketClientService
     */
    private $socketClientService;

    /**
     * @inject
     * @var MessageHandleService
     */
    private $messageHandleService;

    /**
     * 消息事件绑定
     */
    const EVENTS = [
        SocketConstants::EVENT_TALK     => 'onTalk',
        SocketConstants::EVENT_KEYBOARD => 'onKeyboard',
    ];

    /**
     * 连接创建成功回调事件
     *
     * @param Response|Server $server
     * @param Request         $request
     */
    public function onOpen($server, Request $request): void
    {
        // 当前连接的用户
        $user_id = auth('jwt')->user()->getId();

        stdout_log()->notice("用户连接信息 : user_id:{$user_id} | fd:{$request->fd} 时间：" . date('Y-m-d H:i:s'));

        // 判断是否存在异地登录
        $isOnline = $this->socketClientService->isOnlineAll($user_id);

        // 若开启单点登录，则主动关闭之前登录的连接
        if ($isOnline) {
            // TODO 预留
        }

        // 绑定fd与用户关系
        $this->socketClientService->bindRelation($request->fd, $user_id);

        // 加入群聊
        $groupIds = GroupMember::getUserGroupIds($user_id);
        foreach ($groupIds as $group_id) {
            SocketRoom::getInstance()->addRoomMember(strval($group_id), strval($user_id));
        }

        if (!$isOnline) {
            // 推送消息至队列
            push_amqp(new ChatMessageProducer(SocketConstants::EVENT_ONLINE_STATUS, [
                'user_id' => $user_id,
                'status'  => 1,
            ]));
        }
    }

    /**
     * 消息接收回调事件
     *
     * @param Response|Server $server
     * @param Frame           $frame
     */
    public function onMessage($server, Frame $frame): void
    {
        // 判断是否为心跳检测
        if ($frame->data == 'PING') return;

        $result = json_decode($frame->data, true);
        if (!isset(self::EVENTS[$result['event']])) return;

        // 回调事件处理函数
        call_user_func_array([
            $this->messageHandleService,
            self::EVENTS[$result['event']]
        ], [$server, $frame, $result['data']]);
    }

    /**
     * 连接创建成功回调事件
     *
     * @param Response|\Swoole\Server $server
     * @param int                     $fd
     * @param int                     $reactorId
     */
    public function onClose($server, int $fd, int $reactorId): void
    {
        $user_id = $this->socketClientService->findFdUserId($fd);

        stdout_log()->notice("客户端FD:{$fd} 已关闭连接 ，用户ID为【{$user_id}】，关闭时间：" . date('Y-m-d H:i:s'));

        // 删除 fd 绑定关系
        $this->socketClientService->removeRelation($fd);

        // 判断是否存在异地登录
        $isOnline = $this->socketClientService->isOnlineAll($user_id);
        if (!$isOnline) {
            // 推送消息至队列
            push_amqp(new ChatMessageProducer(SocketConstants::EVENT_ONLINE_STATUS, [
                'user_id' => $user_id,
                'status'  => 0,
            ]));
        }
    }
}
