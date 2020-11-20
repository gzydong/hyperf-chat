<?php

declare (strict_types=1);

namespace App\Model;

/**
 * 表情包数据表模型
 *
 * @property int $id 表情包ID
 * @property int $emoticon_id 分组ID
 * @property int $user_id 用户ID
 * @property string $describe 表情描述
 * @property string $url 表情链接
 * @property string $file_suffix 文件前缀
 * @property int $file_size 表情包文件大小
 * @property string $created_at 创建时间
 *
 * @package App\Model
 */
class EmoticonDetail extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'emoticon_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'emoticon_id',
        'user_id',
        'describe',
        'url',
        'file_suffix',
        'file_size',
        'created_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'emoticon_id' => 'integer',
        'user_id' => 'integer',
        'file_size' => 'integer',
        'created_at' => 'integer'
    ];
}
