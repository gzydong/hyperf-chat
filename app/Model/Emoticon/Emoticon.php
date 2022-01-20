<?php

declare (strict_types=1);

namespace App\Model\Emoticon;

use App\Model\BaseModel;

/**
 * 表情包分组数据表模型
 *
 * @property integer $id            分组ID
 * @property string  $name          分组名称
 * @property string  $icon          分组图标
 * @property integer $status        分组状态
 * @property string  $created_at    创建时间
 * @property string  $updated_at    更新时间
 * @package App\Model
 */
class Emoticon extends BaseModel
{
    protected $table = 'emoticon';

    protected $fillable = [
        'name',
        'icon',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'status'     => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
