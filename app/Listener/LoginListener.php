<?php
declare(strict_types=1);

namespace App\Listener;

use App\Cache\IpAddressCache;
use App\Model\Talk\TalkRecordsLogin;
use App\Service\TalkMessageService;
use Hyperf\Event\Contract\ListenerInterface;
use App\Event\LoginEvent;
use Hyperf\Event\Annotation\Listener;

/**
 * @Listener
 */
class LoginListener implements ListenerInterface
{
    public function listen(): array
    {
        // 返回一个该监听器要监听的事件数组，可以同时监听多个事件
        return [
            LoginEvent::class,
        ];
    }

    /**
     * @param object|LoginEvent $event
     */
    public function process(object $event)
    {
        $reason  = '常用设备登录';
        $address = '';

        $result = di()->get(IpAddressCache::class)->getAddressInfo($event->ip);
        if ($result) {
            $arr = array_unique(array_filter([
                $result['country'],
                $result['province'],
                $result['city'],
                $result['isp'],
            ]));

            $address = join(" ", $arr);
        }

        // 判读是否存在异地登录
        $isExist = TalkRecordsLogin::where('user_id', $event->user->id)->where('ip', $event->ip)->exists();
        if (!$isExist) {
            $reason = '非常用设备登录【异常登录】';
        }

        di()->get(TalkMessageService::class)->insertLogin([
            'receiver_id' => $event->user->id,
        ], [
            'user_id'  => $event->user->id,
            'ip'       => $event->ip,
            'platform' => $event->platform,
            'agent'    => $event->agent,
            'address'  => $address,
            'reason'   => $reason,
        ]);
    }
}
