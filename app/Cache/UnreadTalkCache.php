<?php

namespace App\Cache;

/**
 * Class UnreadTalkCache
 * @package App\Cache
 */
class UnreadTalkCache
{
    const KEY = 'hash:unread_talk';

    /**
     * 设置用户未读消息(自增加1)
     *
     * @param int $user_id 用户ID
     * @param string $friend_id 好友ID
     * @return bool
     */
    public function setInc(int $user_id, string $friend_id)
    {
        $num = $this->get($user_id, $friend_id) + 1;

        return (bool)$this->redis()->hset($this->_key($user_id), $friend_id, $num);
    }

    /**
     * 获取用户指定好友的未读消息数
     *
     * @param int $user_id 用户ID
     * @param string $friend_id 好友ID
     * @return int
     */
    public function get(int $user_id, string $friend_id)
    {
        return (int)$this->redis()->hget($this->_key($user_id), $friend_id);
    }

    /**
     * 获取用户未读消息列表
     *
     * @param int $user_id 用户ID
     * @return mixed
     */
    public function getAll(int $user_id)
    {
        return $this->redis()->hgetall($this->_key($user_id));
    }

    /**
     * 清除用户指定好友的未读消息
     *
     * @param int $user_id 用户ID
     * @param string $friend_id 好友ID
     * @return bool
     */
    public function del(int $user_id, string $friend_id)
    {
        return (bool)$this->redis()->hdel($this->_key($user_id), $friend_id);
    }

    /**
     * 清除用户所有好友未读数
     *
     * @param int $user_id
     * @return bool
     */
    public function delAll(int $user_id)
    {
        return (bool)$this->redis()->del($this->_key($user_id));
    }

    /**
     * 获取缓存key
     *
     * @param int $user_id 用户ID
     * @return string
     */
    private function _key(int $user_id)
    {
        return self::KEY . ":{$user_id}";
    }

    /**
     * 获取Redis连接
     */
    private function redis()
    {
        return redis();
    }
}
