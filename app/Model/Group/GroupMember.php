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
 * @property string  $created_at 入群时间
 * @property string  $deleted_at 退群时间
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
        'created_at',
        'deleted_at',
    ];

    protected $casts = [
        'group_id'   => 'integer',
        'user_id'    => 'integer',
        'leader'     => 'integer',
        'is_mute'    => 'integer',
        'is_quit'    => 'integer',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 获取聊天群成员ID
     *
     * @param int $group_id 群聊ID
     * @return array
     */
    public static function getGroupMemberIds(int $group_id)
    {
        return self::where('group_id', $group_id)->where('is_quit', 0)->pluck('user_id')->toArray() ?? [];
    }

    /**
     * 获取用户的群名片
     *
     * @param int $user_id  用户ID
     * @param int $group_id 群ID
     * @return string
     */
    public static function visitCard(int $user_id, int $group_id)
    {
        return self::where('group_id', $group_id)->where('user_id', $user_id)->value('user_card') ?? "";
    }

    /**
     * 获取用户的所有群ID
     *
     * @param int $user_id 用户ID
     * @return array
     */
    public static function getUserGroupIds(int $user_id)
    {
        return self::where('user_id', $user_id)->where('is_quit', 0)->pluck('group_id')->toArray() ?? [];
    }
}
