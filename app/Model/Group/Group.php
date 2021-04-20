<?php
declare (strict_types=1);

namespace App\Model\Group;

use App\Model\BaseModel;

/**
 * 聊天群组数据表模型
 *
 * @property integer $id           群ID
 * @property integer $creator_id   群主ID
 * @property string  $group_name   群名称
 * @property string  $profile      群简介
 * @property integer $avatar       群头像
 * @property integer $max_num      最大群成员数量
 * @property integer $is_overt     是否公开可见[0:否;1:是;]
 * @property integer $is_mute      是否全员禁言 [0:否;1:是;]，提示:不包含群主或管理员
 * @property integer $is_dismiss   是否已解散[0:否;1:是;]
 * @property string  $created_at   创建时间
 * @property string  $dismissed_at 解散时间
 *
 * @package App\Model\Group
 */
class Group extends BaseModel
{
    // 最大成员数量
    const MAX_MEMBER_NUM = 200;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'group';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'creator_id',
        'group_name',
        'profile',
        'avatar',
        'max_num',
        'is_overt',
        'is_mute',
        'is_dismiss',
        'created_at',
        'dismissed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'creator_id' => 'integer',
        'max_num'    => 'integer',
        'is_overt'   => 'integer',
        'is_mute'    => 'integer',
        'is_dismiss' => 'integer',
        'created_at' => 'datetime'
    ];

    /**
     * 获取群聊成员
     */
    public function members()
    {
        return $this->hasMany(GroupMember::class, 'group_id', 'id');
    }

    /**
     * 判断用户是否是管理员
     *
     * @param int       $user_id  用户ID
     * @param int       $group_id 群ID
     * @param int|array $leader   管理员类型[0:普通成员;1:管理员;2:群主;]
     *
     * @return bool
     */
    public static function isManager(int $user_id, int $group_id, $leader = 2)
    {
        return self::where('id', $group_id)->where('creator_id', $user_id)->exists();
    }

    /**
     * 判断群组是否已解散
     *
     * @param int $group_id 群ID
     *
     * @return bool
     */
    public static function isDismiss(int $group_id)
    {
        return self::where('id', $group_id)->where('is_dismiss', 1)->exists();
    }

    /**
     * 判断用户是否是群成员
     *
     * @param int $group_id 群ID
     * @param int $user_id  用户ID
     *
     * @return bool
     */
    public static function isMember(int $group_id, int $user_id)
    {
        return GroupMember::where('group_id', $group_id)->where('user_id', $user_id)->where('is_quit', 0)->exists();
    }
}
