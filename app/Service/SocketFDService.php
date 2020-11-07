<?php

namespace App\Service;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;

/**
 * Socket客户端ID服务
 *
 * @package App\Service
 */
class SocketFDService
{
    /**
     * fd与用户绑定(使用hash 做处理)
     */
    const BIND_FD_TO_USER = 'socket:fd:user';

    /**
     * 使用集合做处理
     */
    const BIND_USER_TO_FDS = 'socket:user:fds';

    /**
     * @inject
     * @var Redis
     */
    private $redis;

    /**
     * 客户端fd与用户ID绑定关系
     *
     * @param int $fd 客户端fd
     * @param int $user_id 用户ID
     * @param string $run_id 服务运行ID（默认当前服务ID）
     * @return mixed
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
     * @param int $fd 客户端ID
     * @param string $run_id 服务运行ID（默认当前服务ID）
     */
    public function removeRelation(int $fd, $run_id = SERVER_RUN_ID)
    {
        $user_id = $this->findFdUserId($fd) | 0;

        $this->redis->hdel(sprintf('%s:%s', self::BIND_FD_TO_USER, $run_id), (string)$fd);
        $this->redis->srem(sprintf('%s:%s:%s', self::BIND_USER_TO_FDS, $run_id, $user_id), $fd);
    }

    /**
     * 检测用户当前是否在线
     *
     * @param int $user_id 用户ID
     * @param string $run_id 服务运行ID（默认当前服务ID）
     * @return bool
     */
    public function isOnline(int $user_id, $run_id = SERVER_RUN_ID): bool
    {
        return $this->redis->scard(sprintf('%s:%s:%s', self::BIND_USER_TO_FDS, $run_id, $user_id)) ? true : false;
    }

    /**
     * 查询客户端fd对应的用户ID
     *
     * @param int $fd 客户端ID
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
     * @param int $user_id 用户ID
     * @param string $run_id 服务运行ID（默认当前服务ID）
     * @return string
     */
    public function findUserFds(int $user_id, $run_id = SERVER_RUN_ID)
    {
        return '';
    }

    /**
     * 清除绑定缓存的信息
     *
     * @param string $run_id 服务运行ID（默认当前服务ID）
     */
    public function removeRedisCache($run_id = SERVER_RUN_ID)
    {
        $this->redis->del(self::BIND_FD_TO_USER);
        $prefix = self::BIND_USER_TO_FDS;
        $iterator = null;
        while (true) {
            $keys = $this->redis->scan($iterator, "{$prefix}*");
            if ($keys === false) {
                return;
            }
            if (!empty($keys)) {
                $this->redis->del(...$keys);
            }
        }
    }
}