<?php

declare (strict_types=1);

namespace App\Model\Chat;

use App\Model\BaseModel;

/**
 * 聊天记录(已删除消息)数据表模型
 *
 * @property int $id 代码块ID
 * @property int $record_id 聊天记录ID
 * @property int $user_id 用户ID
 * @property string $created_at 删除时间
 *
 * @package App\Model\Chat
 */
class ChatRecordsDelete extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chat_records_delete';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'record_id' => 'integer',
        'user_id' => 'integer',
        'created_at' => 'datetime'
    ];
}
