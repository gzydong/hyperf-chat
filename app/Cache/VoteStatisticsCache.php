<?php
declare(strict_types=1);

namespace App\Cache;

use App\Cache\Repository\StringRedis;
use App\Model\Talk\TalkRecordsVote;
use App\Model\Talk\TalkRecordsVoteAnswer;

class VoteStatisticsCache extends StringRedis
{
    protected $name = 'vote-statistic-cache';

    /**
     * 更新投票统计缓存
     *
     * @param int $vote_id 投票ID
     * @return array
     */
    public function updateVoteCache(int $vote_id): array
    {
        $vote = TalkRecordsVote::where('id', $vote_id)->first(['answer_num', 'answered_num', 'answer_option']);
        if (!$vote) return [];

        $answers = TalkRecordsVoteAnswer::where('vote_id', $vote_id)->pluck('option')->toArray();
        $options = array_map(function () {
            return 0;
        }, $vote->answer_option);

        foreach ($answers as $answer) {
            $options[$answer]++;
        }

        $statistics = [
            'count'   => count($answers),
            'options' => $options
        ];

        $this->set(strval($vote_id), json_encode($statistics), 60 * 60 * 24);

        return $statistics;
    }

    /**
     * 获取或更新投票统计缓存
     *
     * @param int $vote_id 投票ID
     * @return array
     */
    public function getOrSetVoteCache(int $vote_id): array
    {
        $cache = $this->get(strval($vote_id));
        return $cache ? json_decode($cache, true) : $this->updateVoteCache($vote_id);
    }
}
