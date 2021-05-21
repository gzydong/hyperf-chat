<?php

namespace App\Service;

use App\Cache\ServerRunID;
use Hyperf\Redis\Redis;

/**
 * Socket客户端ID服务
 *
 * @package App\Service
 */
class SocketClientService
{
    /**
     * fd与用户绑定(使用hash 做处理)
     */
    const BIND_FD_TO_USER = 'ws:fd:user';

    /**
     * 使用集合做处理
     */
    const BIND_USER_TO_FDS = 'ws:user:fds';

    /**
     * @var Redis
     */
    private $redis;

    public function __construct()
    {
        $this->redis = container()->get(Redis::class);
    }

    /**
     * 客户端fd与用户ID绑定关系
     *
     * @param int    $fd      客户端fd
     * @param int    $user_id 用户ID
     * @param string $run_id  服务运行ID（默认当前服务ID）
     */
    public function bindRelation(int $fd, int $user_id, $run_id = SERVER_RUN_ID)
    {
        $this->redis->multi();
        $this->redis->hSet(sprintf('%s:%s', self::BIND_FD_TO_USER, $run_id), (string)$fd, (string)$user_id);
        $this->redis->sadd(sprintf('%s:%s:%s', self::BIND_USER_TO_FDS, $run_id, $user_id), $fd);
        $this->redis->exec();
    }

    /**
     * 解除指定的客户端fd与用户绑定关系
     *
     * @param int    $fd     客户端ID
     * @param string $run_id 服务运行ID（默认当前服务ID）
     */
    public function removeRelation(int $fd, $run_id = SERVER_RUN_ID)
    {
        $user_id = $this->findFdUserId($fd) | 0;

        $this->redis->hdel(sprintf('%s:%s', self::BIND_FD_TO_USER, $run_id), (string)$fd);
        $this->redis->srem(sprintf('%s:%s:%s', self::BIND_USER_TO_FDS, $run_id, $user_id), $fd);
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
        return (bool)$this->redis->scard(sprintf('%s:%s:%s', self::BIND_USER_TO_FDS, $run_id, $user_id));
    }

    /**
     * 检测用户当前是否在线(查询所有在线服务器)
     *
     * @param int   $user_id 用户ID
     * @param array $run_ids 服务运行ID
     * @return bool
     */
    public function isOnlineAll(int $user_id, array $run_ids = [])
    {
        $run_ids = $run_ids ?: ServerRunID::getInstance()->getServerRunIdAll();

        foreach ($run_ids as $run_id => $time) {
            if ($this->isOnline($user_id, $run_id)) return true;
        }

        return false;
    }

    /**
     * 查询客户端fd对应的用户ID
     *
     * @param int    $fd     客户端ID
     * @param string $run_id 服务运行ID（默认当前服务ID）
     * @return int
     */
    public function findFdUserId(int $fd, $run_id = SERVER_RUN_ID)
    {
        return $this->redis->hget(sprintf('%s:%s', self::BIND_FD_TO_USER, $run_id), (string)$fd) ?: 0;
    }

    /**
     * 查询用户的客户端fd集合(用户可能存在多端登录)
     *
     * @param int    $user_id 用户ID
     * @param string $run_id  服务运行ID（默认当前服务ID）
     * @return array
     */
    public function findUserFds(int $user_id, $run_id = SERVER_RUN_ID)
    {
        $arr = $this->redis->smembers(sprintf('%s:%s:%s', self::BIND_USER_TO_FDS, $run_id, $user_id));
        return $arr ? array_map(function ($fd) {
            return (int)$fd;
        }, $arr) : [];
    }

    /**
     * 清除绑定缓存的信息
     *
     * @param string $run_id 服务运行ID
     */
    public function removeRedisCache(string $run_id)
    {
        $this->redis->del(sprintf('%s:%s', self::BIND_FD_TO_USER, $run_id));

        $prefix = sprintf('%s:%s', self::BIND_USER_TO_FDS, $run_id);

        ServerRunID::getInstance()->rem($run_id);

        $iterator = null;
        while (true) {
            $keys = $this->redis->scan($iterator, "{$prefix}*");

            if ($keys === false) return;

            if (!empty($keys)) {
                $this->redis->del(...$keys);
            }
        }
    }
}
