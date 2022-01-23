<?php
declare (strict_types=1);

namespace App\Model;

/**
 * 拆分上传数据表模型
 *
 * @property int    $id               自增ID
 * @property int    $type             上传类型[1:合并文件;2:拆分文件]
 * @property int    $drive            文件驱动
 * @property int    $upload_id        文件上传ID
 * @property int    $user_id          上传的用户ID
 * @property string $original_name    原文件名
 * @property int    $split_index      当前索引块
 * @property int    $split_num        总上传索引块
 * @property string $path             保存路径
 * @property string $file_ext         文件后缀名
 * @property int    $file_size        文件大小
 * @property int    $is_delete        是否删除
 * @property int    $attr             附加信息
 * @property string $created_at       创建时间
 * @property string $updated_at       更新时间
 *
 * @package App\Model
 */
class SplitUpload extends BaseModel
{
    protected $table = 'split_upload';

    public $timestamps = true;

    protected $fillable = [
        'type',
        'drive',
        'upload_id',
        'user_id',
        'original_name',
        'split_index',
        'split_num',
        'path',
        'file_ext',
        'file_size',
        'is_delete',
        'attr',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'type'        => 'integer',
        'drive'       => 'integer',
        'user_id'     => 'integer',
        'split_index' => 'integer',
        'split_num'   => 'integer',
        'file_size'   => 'integer',
        'is_delete'   => 'integer',
        'attr'        => 'json',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];
}
