<?php

namespace App\Cache;

use App\Cache\Repository\HashRedis;

/**
 * 服务运行ID - 缓存助手
 *
 * @package App\Cache
 */
class ServerRunID extends HashRedis
{
    protected $prefix = 'SERVER_RUN_ID';

    protected $name = '';

    /**
     * 运行检测超时时间（单位秒）
     */
    const RUN_OVERTIME = 35;

    /**
     * 获取服务ID列表
     *
     * @param int $type 获取类型[1:正在运行;2:已超时;3:所有]
     * @return array
     */
    public function getServerRunIdAll(int $type = 1): array
    {
        $arr = $this->all();

        if ($type == 3) return $arr;

        $current_time = time();
        return array_filter($arr, function ($value) use ($current_time, $type) {
            if ($type == 1) {
                return ($current_time - intval($value)) <= self::RUN_OVERTIME;
            } else {
                return ($current_time - intval($value)) > self::RUN_OVERTIME;
            }
        });
    }
}
