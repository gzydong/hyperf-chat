<?php
declare(strict_types=1);

namespace App\Listener;

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
        // 推送登录提示信息
        stdout_log()->notice('登录事件回调 ' . $event->user->mobile . ':' . $event->platform . ':' . $event->ip);
    }
}
