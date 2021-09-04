<?php
declare(strict_types=1);

namespace App\Listener;

use App\Event\TalkEvent;
use App\Support\Message;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Event\Annotation\Listener;

/**
 * Websocket 消息监听器
 *
 * @Listener
 */
class TalkMessageListener implements ListenerInterface
{
    public function listen(): array
    {
        // 返回一个该监听器要监听的事件数组，可以同时监听多个事件
        return [
            TalkEvent::class,
        ];
    }

    /**
     * @param object|TalkEvent $event
     */
    public function process(object $event)
    {
        Message::publish(Message::create($event->event_name, $event->data));
    }
}
