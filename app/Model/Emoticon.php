<?php

declare (strict_types=1);

namespace App\Model;

/**
 * 表情包分组数据表模型
 *
 * @property int $id 分组ID
 * @property string $name 分组名称
 * @property string $url 默认表情
 * @property string $created_at 创建时间
 *
 * @package App\Model
 */
class Emoticon extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'emoticon';

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
        'created_at' => 'datetime'
    ];
}
