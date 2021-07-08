<?php

declare(strict_types=1);

namespace App\Process;

use App\Service\Message\SubscribeHandleService;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;

/**
 * @Process(name="RedisWebsocketSubscribe")
 */
class RedisWebsocketSubscribe extends AbstractProcess
{
    /**
     * 订阅的通道
     *
     * @var string[]
     */
    private $chans = ['websocket'];

    /**
     * @var SubscribeHandleService
     */
    private $handleService;

    public function handle(): void
    {
        $this->handleService = container()->get(SubscribeHandleService::class);

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

        if (!isset(SubscribeHandleService::EVENTS[$data['event']])) return;

        $this->handleService->{SubscribeHandleService::EVENTS[$data['event']]}($data);
    }

    public function isEnable($server): bool
    {
        return true;
    }
}
