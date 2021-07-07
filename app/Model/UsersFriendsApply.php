<?php

declare (strict_types=1);

namespace App\Model;

/**
 * 好友添加申请数据表模型
 *
 * @property int    $id         申请ID
 * @property int    $user_id    用户ID
 * @property int    $friend_id  朋友ID
 * @property int    $status     申请状态
 * @property string $remark     备注说明
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @package App\Model
 */
class UsersFriendsApply extends BaseModel
{
    protected $table = 'users_friends_apply';

    protected $fillable = [
        'user_id',
        'friend_id',
        'status',
        'remarks',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id'         => 'integer',
        'user_id'    => 'integer',
        'friend_id'  => 'integer',
        'status'     => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
