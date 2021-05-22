<?php

namespace App\Cache;

use App\Cache\Repository\SetGroupRedis;

/**
 * 注:用户ID与客户端ID绑定(一对多关系)
 *
 * @package App\Cache
 */
class SocketUserBindFds extends SetGroupRedis
{
    protected $name = 'ws:user-fds';

    /**
     * @param int    $fd      客户端ID
     * @param int    $user_id 用户ID
     * @param string $run_id  服务运行ID（默认当前服务ID）
     * @return bool|int
     */
    public function bind(int $fd, int $user_id, $run_id = SERVER_RUN_ID)
    {
        return $this->add($this->filter([$run_id, $user_id]), $fd);
    }

    /**
     * @param int    $fd     客户端ID
     * @param string $run_id 服务运行ID（默认当前服务ID）
     * @return int
     */
    public function unBind(int $fd, int $user_id, $run_id = SERVER_RUN_ID)
    {
        return $this->rem($this->filter([$run_id, $user_id]), $fd);
    }

    /**
     * 检测用户当前是否在线（指定运行服务器）
     *
     * @param int    $user_id 用户ID
     * @param string $run_id  服务运行ID（默认当前服务ID）
     * @return bool
     */
    public function isOnline(int $user_id, $run_id = SERVER_RUN_ID): bool
    {
        return (bool)$this->count($this->filter([$run_id, $user_id]));
    }

    /**
     * 检测用户当前是否在线(查询所有在线服务器)
     *
     * @param int   $user_id 用户ID
     * @param array $run_ids 服务运行ID（默认当前服务ID）
     * @return bool
     */
    public function isOnlineAll(int $user_id, array $run_ids = []): bool
    {
        $run_ids = $run_ids ?: ServerRunID::getInstance()->getServerRunIdAll();

        foreach ($run_ids as $run_id => $time) {
            if ($this->isOnline($user_id, $run_id)) return true;
        }

        return false;
    }

    /**
     * 查询用户的客户端fd集合(用户可能存在多端登录)
     *
     * @param int    $user_id 用户ID
     * @param string $run_id  服务运行ID（默认当前服务ID）
     * @return array
     */
    public function findFds(int $user_id, $run_id = SERVER_RUN_ID): array
    {
        $arr = $this->all($this->filter([$run_id, $user_id]));
        foreach ($arr as $k => $value) {
            $arr[$k] = intval($value);
        }

        return $arr;
    }

    public function getCachePrefix(string $run_id): string
    {
        return $this->getCacheKey($run_id);
    }
}
