<?php

namespace App\Cache;

use App\Cache\Repository\HashRedis;

/**
 * 聊天最新消息 - 缓存助手
 *
 * @package App\Cache
 */
class LastMessage extends HashRedis
{
    public $name = 'last-message';

    /**
     * 保存最后一条缓存信息
     *
     * @param int   $type    聊天类型[1:私信;2:群聊;]
     * @param int   $sender  发送者ID
     * @param int   $receive 接收者ID
     * @param array $message
     */
    public function save(int $type, int $sender, int $receive, array $message)
    {
        $this->add($this->flag($type, $sender, $receive), json_encode($message));
    }

    /**
     * 读取最后一条缓存信息
     *
     * @param int $type    聊天类型[1:私信;2:群聊;3:机器人;]
     * @param int $sender  发送者ID
     * @param int $receive 接收者ID
     * @return array
     */
    public function read(int $type, int $sender, int $receive): array
    {
        $message = $this->get($this->flag($type, $sender, $receive));

        return $message ? json_decode($message, true) : [];
    }

    /**
     * 获取 Hash 成员 key
     *
     * @return string
     */
    public function flag(int $type, int $sender, int $receive)
    {
        // 群聊信息(非私信)，发送者ID重置为零
        if ($type == 2) $sender = 0;

        [$sender, $receive] = $sender <= $receive ? [$sender, $receive] : [$receive, $sender];

        return sprintf("%s_%s_%s", $type, $sender, $receive);
    }
}
