<?php

namespace App\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use App\Event\UserLogin;
use Hyperf\Event\Annotation\Listener;

/**
 * @Listener
 */
class UserLoginListener implements ListenerInterface
{
    public function listen(): array
    {
        // 返回一个该监听器要监听的事件数组，可以同时监听多个事件
        return [
            UserLogin::class,
        ];
    }

    /**
     * @param object|UserLogin $event
     */
    public function process(object $event)
    {
        // echo $event->user->id . ':' . $event->platform . ':' . $event->ip;
        // 推送登录提示信息
    }
}
