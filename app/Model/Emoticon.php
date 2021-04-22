<?php

declare (strict_types=1);

namespace App\Model;

/**
 * 表情包分组数据表模型
 *
 * @property int    $id         分组ID
 * @property string $name       分组名称
 * @property string $url        默认表情
 * @property string $created_at 创建时间
 * @package App\Model
 */
class Emoticon extends BaseModel
{
    protected $table = 'emoticon';

    protected $fillable = [];

    protected $casts = [
        'id'         => 'integer',
        'created_at' => 'datetime'
    ];
}
