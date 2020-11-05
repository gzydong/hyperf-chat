<?php

declare (strict_types=1);

namespace App\Model\Group;

use App\Model\BaseModel;

/**
 * @property int $id
 * @property int $group_id
 * @property int $user_id
 * @property int $group_owner
 * @property int $status
 * @property string $visit_card
 * @property \Carbon\Carbon $created_at
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
}
