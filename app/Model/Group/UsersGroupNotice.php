<?php

declare (strict_types=1);

namespace App\Model\Group;

use App\Model\BaseModel;

/**
 * 用户群组[公告消息]数据表模型
 *
 * @property int $id 群公告ID
 * @property int $group_id 群ID
 * @property int $user_id 发布者ID
 * @property string $title 公告标题
 * @property string $content 公告内容
 * @property int $is_delete 是否删除[0:否;1:是]
 * @property string $created_at 发布时间
 * @property string $updated_at 修改时间
 * @property string $deleted_at 删除时间
 *
 * @package App\Model\Group
 */
class UsersGroupNotice extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_group_notice';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id',
        'user_id',
        'title',
        'content',
        'is_delete',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'group_id' => 'integer',
        'user_id' => 'integer',
        'is_delete' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
