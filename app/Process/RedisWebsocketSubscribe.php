<?php
declare(strict_types=1);

namespace App\Process;

use App\Constant\RedisSubscribeChan;
use App\Service\Message\SubscribeHandleService;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;

/**
 * Websocket 消息订阅处理服务
 * @Process(name="RedisWebsocketSubscribe")
 */
class RedisWebsocketSubscribe extends AbstractProcess
{
    /**
     * 订阅的通道
     *
     * @var string[]
     */
    private $chans = [
        RedisSubscribeChan::WEBSOCKET_CHAN
    ];

    /**
     * @var SubscribeHandleService
     */
    private $handleService;

    /**
     * 执行入口
     */
    public function handle(): void
    {
        $this->handleService = di()->get(SubscribeHandleService::class);

        redis()->subscribe($this->chans, [$this, 'subscribe']);
    }

    /**
     * 订阅处理逻辑
     *
     * @param        $redis
     * @param string $chan
     * @param string $message
     */
    public function subscribe($redis, string $chan, string $message)
    {
        //echo PHP_EOL . "chan : $chan , msg : $message";
        $data = json_decode($message, true);

        $this->handleService->handle($data);
    }
}
