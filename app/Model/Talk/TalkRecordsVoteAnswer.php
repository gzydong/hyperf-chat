<?php
declare(strict_types=1);

namespace App\Model\Talk;

use App\Model\BaseModel;

/**
 * Class TalkRecordsVoteAnswer
 *
 * @property int    $id           自增ID
 * @property int    $vote_id      投票ID
 * @property int    $user_id      投票用户
 * @property string $option       投票选项
 * @property string $created_at   投票时间
 * @package App\Model\Chat
 */
class TalkRecordsVoteAnswer extends BaseModel
{
    protected $table = 'talk_records_vote_answer';

    protected $fillable = [
        'vote_id',
        'user_id',
        'option',
        'created_at',
    ];

    protected $casts = [
        'vote_id'    => 'integer',
        'user_id'    => 'integer',
        'created_at' => 'datetime',
    ];
}
