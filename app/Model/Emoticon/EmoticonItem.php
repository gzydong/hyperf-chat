<?php
declare (strict_types=1);

namespace App\Model\Emoticon;

use App\Model\BaseModel;

/**
 * 表情包数据表模型
 *
 * @property int    $id          表情包详情ID
 * @property int    $emoticon_id 表情分组ID
 * @property int    $user_id     用户ID
 * @property string $describe    表情描述
 * @property string $url         表情链接
 * @property string $file_suffix 文件前缀
 * @property int    $file_size   表情包文件大小
 * @property string $created_at  创建时间
 * @property string $updated_at  更新时间
 *
 * @package App\Model
 */
class EmoticonItem extends BaseModel
{
    protected $table = 'emoticon_item';

    public $timestamps = true;

    protected $fillable = [
        'emoticon_id',
        'user_id',
        'describe',
        'url',
        'file_suffix',
        'file_size',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'emoticon_id' => 'integer',
        'user_id'     => 'integer',
        'file_size'   => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];
}
