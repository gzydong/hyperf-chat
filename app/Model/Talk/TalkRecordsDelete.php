<?php

declare (strict_types=1);

namespace App\Model\Talk;

use App\Model\BaseModel;

/**
 * 聊天记录(已删除消息)数据表模型
 *
 * @property int    $id         代码块ID
 * @property int    $record_id  聊天记录ID
 * @property int    $user_id    用户ID
 * @property string $created_at 删除时间
 * @package App\Model\Chat
 */
class TalkRecordsDelete extends BaseModel
{
    protected $table = 'talk_records_delete';

    protected $fillable = [];

    protected $casts = [
        'record_id'  => 'integer',
        'user_id'    => 'integer',
        'created_at' => 'datetime'
    ];
}
