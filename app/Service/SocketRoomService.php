<?php

namespace App\Service;

/**
 * 聊天室房间服务
 *
 * Class SocketRoomService
 * @package App\Service
 */
class SocketRoomService
{
    const ROOM = 'ws:room';

    /**
     * 获取房间名
     *
     * @param string|integer $room 房间名
     * @return string
     */
    public function getRoomName($room)
    {
        return sprintf('%s:%s', self::ROOM, $room);
    }

    /**
     * 获取房间成员
     *
     * @param string $room 房间名
     * @return array
     */
    public function getRoomMembers(string $room)
    {
        return redis()->sMembers($this->getRoomName($room));
    }

    /**
     * 成员加入房间
     *
     * @param int $usr_id 用户ID
     * @param string|array $room 房间名
     * @return bool|int
     */
    public function addRoomMember(int $usr_id, $room)
    {
        return redis()->sAdd($this->getRoomName($room), $usr_id);
    }

    /**
     * 成员退出房间
     *
     * @param string|array $room 房间名
     * @param string|array $members 用户ID
     * @return int
     */
    public function delRoomMember($room, $members)
    {
        return redis()->sRem($this->getRoomName($room), $members);
    }

    /**
     * 删除房间
     *
     * @param string|int $room 房间名
     * @return int
     */
    public function delRoom($room)
    {
        return redis()->del($this->getRoomName($room));
    }
}
