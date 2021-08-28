<?php
declare(strict_types=1);

namespace App\Cache;

use App\Cache\Repository\StringRedis;
use App\Model\Group\Group;

class GroupCache extends StringRedis
{
    protected $name = 'group-cache';

    /**
     * 更新缓存
     *
     * @param int $group_id
     * @return array
     */
    public function updateCache(int $group_id): array
    {
        $group = Group::where('id', $group_id)->first();

        if (!$group) return [];

        $group = $group->toArray();

        $this->set(strval($group_id), json_encode($group), 60 * 60 * 1);

        return $group;
    }

    /**
     * 获取或更新群组缓存
     *
     * @param int $group_id 群组ID
     * @return array
     */
    public function getOrSetCache(int $group_id): array
    {
        $cache = $this->get(strval($group_id));
        return $cache ? json_decode($cache, true) : $this->updateCache($group_id);
    }
}
