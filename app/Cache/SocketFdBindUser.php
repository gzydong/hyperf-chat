<?php

namespace App\Cache;

use App\Cache\Repository\HashGroupRedis;

/**
 * 注:客户端ID与用户ID绑定(多对一关系)
 *
 * @package App\Cache
 */
class SocketFdBindUser extends HashGroupRedis
{
    protected $name = 'ws:fd-user';

    /**
     * 添加绑定
     *
     * @param int    $fd      客户端ID
     * @param int    $user_id 用户ID
     * @param string $run_id  服务运行ID（默认当前服务ID）
     * @return bool|int
     */
    public function bind(int $fd, int $user_id, $run_id = SERVER_RUN_ID)
    {
        return $this->add($run_id, strval($fd), $user_id);
    }

    /**
     * 解除绑定
     *
     * @param string $run_id 服务运行ID（默认当前服务ID）
     * @return bool|int
     */
    public function unBind(int $fd, $run_id = SERVER_RUN_ID)
    {
        return $this->rem($run_id, strval($fd));
    }

    /**
     * 查询客户端 FD 对应的用户ID
     *
     * @param int    $fd     客户端ID
     * @param string $run_id 服务运行ID（默认当前服务ID）
     * @return int
     */
    public function findUserId(int $fd, $run_id = SERVER_RUN_ID): int
    {
        return (int)$this->get($run_id, strval($fd)) ?: 0;
    }
}
