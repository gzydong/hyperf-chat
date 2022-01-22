<?php

declare (strict_types=1);

namespace App\Model\Talk;

use App\Model\BaseModel;

/**
 * 聊天记录(入群/退群消息)数据表模型
 *
 * @property int    $id               投票ID
 * @property int    $record_id        消息记录ID
 * @property int    $user_id          用户ID
 * @property string $title            投票标题
 * @property int    $answer_mode      投票模式
 * @property string $answer_option    投票选项
 * @property int    $answer_num       应答人数
 * @property int    $answered_num     已答人数
 * @property int    $status           投票状态
 * @property string $created_at       创建时间
 * @property string $updated_at       更新时间
 * @package App\Model\Chat
 */
class TalkRecordsVote extends BaseModel
{
    protected $table = 'talk_records_vote';

    public $timestamps = true;

    protected $fillable = [
        'record_id',
        'user_id',
        'title',
        'answer_mode',
        'answer_option',
        'answer_num',
        'answered_num',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'record_id'     => 'integer',
        'user_id'       => 'integer',
        'answer_mode'   => 'integer',
        'answer_num'    => 'integer',
        'answered_num'  => 'integer',
        'status'        => 'integer',
        'answer_option' => 'array',
    ];
}
