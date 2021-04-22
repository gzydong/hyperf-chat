<?php
declare (strict_types=1);

namespace App\Model\Group;

use App\Model\BaseModel;

/**
 * 聊天群组[公告消息]数据表模型
 *
 * @property integer $id            群公告ID
 * @property integer $group_id      群组ID
 * @property integer $creator_id    创建者用户ID
 * @property string  $title         公告标题
 * @property string  $content       公告内容
 * @property integer $is_top        是否置顶[0:否;1:是;]
 * @property integer $is_delete     是否删除[0:否;1:是;]
 * @property integer $is_confirm    是否需群成员确认公告[0:否;1:是;]
 * @property array   $confirm_users 已确认成员
 * @property string  $created_at    创建时间
 * @property string  $updated_at    更新时间
 * @property string  $deleted_at    删除时间
 * @package App\Model\Group
 */
class GroupNotice extends BaseModel
{
    protected $table = 'group_notice';

    protected $fillable = [
        'group_id',
        'creator_id',
        'title',
        'content',
        'is_top',
        'is_delete',
        'is_confirm',
        'confirm_users',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'group_id'   => 'integer',
        'creator_id' => 'integer',
        'is_top'     => 'integer',
        'is_delete'  => 'integer',
        'is_confirm' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
