<?php

declare (strict_types=1);

namespace App\Model\Group;

use App\Model\BaseModel;

/**
 * 用户群组[成员]数据表模型
 *
 * @property int $id 群成员ID
 * @property int $group_id 群组ID
 * @property int $user_id 用户ID
 * @property int $group_owner 是否群主[0:否;1:是;]
 * @property int $status 退群状态[0:正常状态;1:已退群;]
 * @property string $visit_card 用户群名片
 * @property string $created_at 入群时间
 *
 * @package App\Model\Group
 */
class UsersGroupMember extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_group_member';

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
        'group_id' => 'integer',
        'user_id' => 'integer',
        'group_owner' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime'
    ];

    /**
     * 获取聊天群成员ID
     *
     * @param int $group_id 群聊ID
     * @return mixed
     */
    public static function getGroupMemberIds(int $group_id)
    {
        return self::where('group_id', $group_id)->where('status', 0)->pluck('user_id')->toArray();
    }

    /**
     * 获取用户的群名片
     *
     * @param int $user_id 用户ID
     * @param int $group_id 群ID
     * @return mixed
     */
    public static function visitCard(int $user_id, int $group_id)
    {
        return self::where('group_id', $group_id)->where('user_id', $user_id)->value('visit_card');
    }

    /**
     * 获取用户的所有群ID
     *
     * @param int $user_id
     * @return array
     */
    public static function getUserGroupIds(int $user_id)
    {
        return self::where('user_id', $user_id)->where('status', 0)->pluck('group_id')->toArray();
    }
}
