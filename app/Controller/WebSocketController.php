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

use Hyperf\Di\Annotation\Inject;
use App\Cache\SocketRoom;
use App\Service\Group\GroupMemberService;
use App\Service\Message\ReceiveHandleService;
use App\Constant\TalkEventConstant;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Swoole\Http\Request;
use Swoole\Websocket\Frame;
use Swoole\Http\Response;
use Swoole\WebSocket\Server;
use App\Service\SocketClientService;
use App\Event\TalkEvent;

/**
 * Class WebSocketController
 *
 * @package App\Controller
 */
class WebSocketController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    /**
     * @Inject
     * @var SocketClientService
     */
    protected $client;

    /**
     * @Inject
     * @var ReceiveHandleService
     */
    protected $receiveHandle;

    /**
     * 连接创建成功回调事件
     *
     * @param Response|Server $server
     * @param Request         $request
     */
    public function onOpen($server, Request $request): void
    {
        $server->push($request->fd, json_encode([
            "event"   => "connect",
            "content" => [
                "ping_interval" => 20,
                "ping_timeout"  => 20 * 3,
            ],
        ]));

        // 当前连接的用户
        $user_id = auth('jwt')->user()->getId();

        stdout_log()->notice("用户连接信息 : user_id:{$user_id} | fd:{$request->fd} 时间：" . date('Y-m-d H:i:s'));

        // 判断是否存在异地登录
        $isOnline = $this->client->isOnlineAll($user_id);

        // 绑定fd与用户关系
        $this->client->bind($request->fd, $user_id);

        // 加入群聊
        $groupIds = di()->get(GroupMemberService::class)->getUserGroupIds($user_id);
        foreach ($groupIds as $group_id) {
            SocketRoom::getInstance()->addRoomMember(strval($group_id), strval($user_id));
        }

        if (!$isOnline) {
            event()->dispatch(new TalkEvent(TalkEventConstant::EVENT_LOGIN, [
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
        $result = json_decode($frame->data, true);

        // 判断是否为心跳检测
        if ($result['event'] == 'heartbeat') {
            $server->push($frame->fd, json_encode(['event' => "heartbeat", 'content' => "pong"]));
            return;
        }

        if (!isset(ReceiveHandleService::EVENTS[$result['event']])) {
            return;
        }

        // 回调处理
        call_user_func_array([$this->receiveHandle, ReceiveHandleService::EVENTS[$result['event']]], [
            $server, $frame, $result['data']
        ]);
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
        $user_id = $this->client->findFdUserId($fd);

        stdout_log()->notice("客户端FD:{$fd} 已关闭连接 ，用户ID为【{$user_id}】，关闭时间：" . date('Y-m-d H:i:s'));

        // 删除 fd 绑定关系
        $this->client->unbind($fd);

        // 判断是否存在异地登录
        $isOnline = $this->client->isOnlineAll($user_id);
        if ($isOnline) return;

        event()->dispatch(new TalkEvent(TalkEventConstant::EVENT_LOGIN, [
            'user_id' => $user_id,
            'status'  => 0,
        ]));
    }
}
