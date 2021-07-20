<?php
declare(strict_types=1);

namespace App\Event;

class TalkEvent
{
    /**
     * @var string 消息事件名
     */
    public $event_name;

    /**
     * @var array 消息数据
     */
    public $data;

    /**
     * TalkMessageEvent constructor.
     *
     * @param string $event_name
     * @param array  $data
     */
    public function __construct(string $event_name, array $data = [])
    {
        $this->event_name = $event_name;
        $this->data       = $data;
    }
}
