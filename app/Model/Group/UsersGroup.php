<?php

declare (strict_types=1);

namespace App\Model\Group;

use App\Model\BaseModel;

/**
 * 用户群组数据表模型
 *
 * @property int $id 群ID
 * @property int $user_id 群组ID
 * @property string $group_name 群名称
 * @property string $group_profile 群简介
 * @property int $status 群状态
 * @property string $avatar 群头像
 * @property string $created_at 创建时间
 *
 * @package App\Model\Group
 */
class UsersGroup extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_group';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'group_name',
        'group_profile',
        'status',
        'avatar',
        'created_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime'
    ];

    /**
     * 获取群聊成员
     */
    public function members()
    {
        return $this->hasMany(UsersGroupMember::class, 'group_id', 'id');
    }

    /**
     * 判断用户是否是管理员
     *
     * @param int $user_id 用户ID
     * @param int $group_id 群ID
     * @return mixed
     */
    public static function isManager(int $user_id, int $group_id)
    {
        return self::where('id', $group_id)->where('user_id', $user_id)->exists();
    }

    /**
     * 判断用户是否是群成员
     *
     * @param int $group_id 群ID
     * @param int $user_id 用户ID
     * @return bool
     */
    public static function isMember(int $group_id, int $user_id)
    {
        return UsersGroupMember::where('group_id', $group_id)->where('user_id', $user_id)->where('status', 0)->exists();
    }
}
