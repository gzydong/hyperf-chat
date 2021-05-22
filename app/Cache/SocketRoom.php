<?php

namespace App\Cache;

use App\Cache\Repository\SetGroupRedis;

/**
 * 聊天室 - 缓存助手
 *
 * @package App\Cache
 */
class SocketRoom extends SetGroupRedis
{
    protected $name = 'ws:room';

    /**
     * 获取房间名
     *
     * @param string|integer $room 房间名
     * @return string
     */
    public function getRoomName($room)
    {
        return $this->getCacheKey($room);
    }

    /**
     * 获取房间中所有的用户ID
     *
     * @param string $room 房间名
     * @return array
     */
    public function getRoomMembers(string $room)
    {
        return $this->all($room);
    }

    /**
     * 添加房间成员
     *
     * @param string $room      房间名
     * @param string ...$member 用户ID
     * @return bool|int
     */
    public function addRoomMember(string $room, string ...$member)
    {
        return $this->add($room, ...$member);
    }

    public function delRoomMember($room, string ...$member)
    {
        return $this->rem($room, ...$member);
    }

    /**
     * 删除房间
     *
     * @param string|int $room 房间名
     * @return int
     */
    public function delRoom($room)
    {
        return $this->delete($room);
    }
}
