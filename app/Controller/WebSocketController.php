<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\Utils\Codec\Json;
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
     * @inject
     * @var SocketFDService
     */
    private $socketFDService;

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
        stdout_log()->notice("用户连接信息 : user_id:{$userInfo['user_id']} | fd:{$request->fd} | data:" . Json::encode($userInfo));

        // 绑定fd与用户关系
        $this->socketFDService->bindRelation($request->fd, $userInfo['user_id']);

        $ip = config('ip_address');
        $server->push($request->fd, "成功连接[{$ip}],IM 服务器");
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

        $ip = config('ip_address');
        $producer = container()->get(Producer::class);
        $producer->produce(new ChatMessageProducer("我是来自[{$ip} 服务器的消息]，{$frame->data}"));
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

        stdout_log()->notice("客户端FD:{$fd} 已关闭连接,用户ID为【{$user_id}】");

        // 解除fd关系
        $this->socketFDService->removeRelation($fd);

        // ... 包装推送消息至队列
    }
}
