<?php

declare (strict_types=1);

namespace App\Model\Contact;

use App\Model\BaseModel;

/**
 * 表情包收藏数据表模型
 *
 * @property int    $id
 * @property int    $user_id          用户ID
 * @property int    $friend_id        好友ID
 * @property string $remark           好友备注
 * @property int    $status           好友状态
 * @property string $created_at       创建时间
 * @property string $updated_at       更新时间
 * @package App\Model
 */
class Contact extends BaseModel
{
    protected $table = 'contact';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'friend_id',
        'status',
        'remark',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'user_id'    => 'integer',
        'friend_id'  => 'integer',
        'status'     => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
