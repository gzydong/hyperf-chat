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
    /**
     * 最大成员数量
     */
    const MAX_MEMBER_NUM = 200;

    protected $table = 'group';

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
        'updated_at',
        'dismissed_at',
    ];

    protected $casts = [
        'creator_id' => 'integer',
        'max_num'    => 'integer',
        'is_overt'   => 'integer',
        'is_mute'    => 'integer',
        'is_dismiss' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
