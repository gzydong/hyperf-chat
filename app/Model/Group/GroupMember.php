<?php
declare (strict_types=1);

namespace App\Model\Group;

use App\Model\BaseModel;

/**
 * 聊天群组[成员]数据表模型
 *
 * @property integer $id         群成员ID
 * @property integer $group_id   群组ID
 * @property integer $user_id    用户ID
 * @property integer $leader     成员属性[0:普通成员;1:管理员;2:群主;]
 * @property integer $is_mute    是否禁言[0:否;1:是;]
 * @property integer $is_quit    是否退群[0:否;1:是;]
 * @property string  $user_card  群名片
 * @property string  $created_at 创建时间
 * @property string  $updated_at 更新时间
 * @property string  $deleted_at 删除时间
 * @package App\Model\Group
 */
class GroupMember extends BaseModel
{
    protected $table = 'group_member';

    protected $fillable = [
        'group_id',
        'user_id',
        'leader',
        'is_mute',
        'is_quit',
        'user_card',
        'last_record_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'group_id'   => 'integer',
        'user_id'    => 'integer',
        'leader'     => 'integer',
        'is_mute'    => 'integer',
        'is_quit'    => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
