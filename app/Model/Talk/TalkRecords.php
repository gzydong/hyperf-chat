<?php

declare (strict_types=1);

namespace App\Model\Talk;

use App\Model\BaseModel;

/**
 * 聊天记录数据表模型
 *
 * @property integer $id                聊天消息ID
 * @property int     $talk_type         对话类型
 * @property int     $msg_type          消息类型
 * @property int     $user_id           发送者ID
 * @property int     $receiver_id       接收者ID
 * @property int     $is_revoke         是否撤回消息
 * @property int     $is_mark           是否重要消息
 * @property int     $is_read           是否已读
 * @property int     $quote_id          引用消息ID
 * @property string  $warn_users        引用好友
 * @property string  $content           文本消息
 * @property string  $created_at        创建时间
 * @property string  $updated_at        更新时间
 * @package App\Model\Chat
 */
class TalkRecords extends BaseModel
{
    protected $table = 'talk_records';

    public $timestamps = true;

    protected $fillable = [
        'talk_type',
        'msg_type',
        'user_id',
        'receiver_id',
        'is_revoke',
        'is_mark',
        'is_read',
        'quote_id',
        'warn_users',
        'content',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'talk_type'   => 'integer',
        'msg_type'    => 'integer',
        'user_id'     => 'integer',
        'receiver_id' => 'integer',
        'is_revoke'   => 'integer',
        'is_mark'     => 'integer',
        'is_read'     => 'integer',
        'quote_id'    => 'integer',
    ];
}
