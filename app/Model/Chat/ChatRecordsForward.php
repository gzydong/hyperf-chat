<?php

declare (strict_types=1);

namespace App\Model\Chat;

use App\Model\BaseModel;

/**
 * 聊天记录(转发消息)数据表模型
 *
 * @property int $id 转发ID
 * @property int $record_id 聊天记录ID
 * @property int $user_id 用户ID
 * @property string $records_id 聊天记录ID，多个用英文','拼接
 * @property string $text 缓存信息
 * @property int $created_at 转发时间
 *
 * @package App\Model\Chat
 */
class ChatRecordsForward extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chat_records_forward';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'record_id', 'user_id', 'records_id','text','created_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'record_id' => 'integer',
        'user_id' => 'integer',
    ];
}
