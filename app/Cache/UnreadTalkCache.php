<?php

namespace App\Cache;

use App\Cache\Repository\HashRedis;
use App\Constant\TalkModeConstant;

/**
 * 私信消息未读数 - 缓存助手
 *
 * @package App\Cache
 */
class UnreadTalkCache extends HashRedis
{
    public $name = 'unread-talk';

    /**
     * 消息未读数自增
     *
     * @param int $sender  发送者ID
     * @param int $receive 接收者ID
     * @param int $talk_type 消息类型
     */
    public function increment(int $sender, int $receive,int $talk_type)
    {
        $this->incr($this->flag($talk_type,$sender, $receive), 1);
    }

    /**
     * 读取消息未读数
     *
     * @param int $sender  发送者ID
     * @param int $receive 接收者ID
     * @param int $talk_type 消息类型
     * @return int
     */
    public function read(int $sender, int $receive,int $talk_type): int
    {
        return (int)$this->get($this->flag($talk_type,$sender, $receive));
    }

    /**
     * 消息未读数清空
     *
     * @param int $sender  发送者ID
     * @param int $receive 接收者ID
     * @param int $talk_type 消息类型
     */
    public function reset(int $sender, int $receive,int $talk_type)
    {
        $this->rem($this->flag($talk_type,$sender, $receive));
    }

    /**
     * 获取 Hash 成员 key
     *
     * @param int $sender
     * @param int $receive
     * @return string
     */
    public function flag(int $talk_type,int $sender, int $receive): string
    {
        return sprintf("%s_%s_%s",$talk_type, $sender, $receive);
    }

    /**
     * 读取指定用户的未读消息列表
     *
     * @param int $user_id 用户ID
     * @return array
     */
    public function reads(int $user_id): array
    {
        $iterator = null;
        $arr      = [];
        while ($elements = $this->redis()->hscan($this->getCacheKey(), $iterator, '*_*_' . $user_id, 20)) {
            foreach ($elements as $key => $value) {
                $keyArr=explode('_', $key);
                $arr[$keyArr[1]]=['talk_type'=>$keyArr[0],'num'=>$value];
            }
        }

        return $arr;
    }
}
