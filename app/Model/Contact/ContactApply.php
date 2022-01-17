<?php

declare (strict_types=1);

namespace App\Model\Contact;

use App\Model\BaseModel;

/**
 * 好友添加申请数据表模型
 *
 * @property int    $id         申请ID
 * @property int    $user_id    用户ID
 * @property int    $friend_id  朋友ID
 * @property string $remark     备注说明
 * @property string $created_at 创建时间
 * @package App\ModelÒ
 */
class ContactApply extends BaseModel
{
    protected $table = 'contact_apply';

    protected $fillable = [
        'user_id',
        'friend_id',
        'remark',
        'created_at',
    ];

    protected $casts = [
        'user_id'    => 'integer',
        'friend_id'  => 'integer',
        'created_at' => 'datetime'
    ];
}
