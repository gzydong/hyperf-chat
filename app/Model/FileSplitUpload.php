<?php

declare (strict_types=1);

namespace App\Model;

/**
 * 文件拆分上传数据表模型
 *
 * @property int $id 临时文件ID
 * @property int $file_type 上传类型[1:合并文件;2:拆分文件]
 * @property int $user_id 上传的用户ID
 * @property string $hash_name 临时文件hash名
 * @property string $original_name 原文件名
 * @property int $split_index 当前索引块
 * @property int $split_num 总上传索引块
 * @property string $save_dir 文件的临时保存路径
 * @property string $file_ext 文件后缀名
 * @property int $file_size 临时文件大小
 * @property int $is_delete 文件是否已被删除[1:是;0:否;]
 * @property int $upload_at 文件上传时间
 *
 * @package App\Model
 */
class FileSplitUpload extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'file_split_upload';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'file_type',
        'user_id',
        'hash_name',
        'original_name',
        'split_index',
        'split_num',
        'save_dir',
        'file_ext',
        'file_size',
        'is_delete',
        'upload_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'file_type' => 'integer',
        'user_id' => 'integer',
        'split_index' => 'integer',
        'split_num' => 'integer',
        'file_size' => 'integer',
        'is_delete' => 'integer',
        'upload_at' => 'integer'
    ];
}
