<?php
declare(strict_types=1);

namespace App\Cache;

use App\Cache\Repository\StringRedis;
use App\Model\Talk\TalkRecordsVoteAnswer;

class VoteCache extends StringRedis
{
    protected $name = 'vote-cache';

    /**
     * 更新投票缓存
     *
     * @param int $vote_id 投票ID
     * @return array
     */
    public function updateCache(int $vote_id): array
    {
        $vote_users = TalkRecordsVoteAnswer::where('vote_id', $vote_id)->pluck('user_id')->toArray();
        $vote_users = array_unique($vote_users);
        $this->set(strval($vote_id), json_encode($vote_users), 60 * 60 * 24);

        return $vote_users;
    }

    /**
     * 获取或更新投票缓存
     *
     * @param int $vote_id 投票ID
     * @return array
     */
    public function getOrSetCache(int $vote_id): array
    {
        $cache = $this->get(strval($vote_id));
        return $cache ? json_decode($cache, true) : $this->updateCache($vote_id);
    }
}
