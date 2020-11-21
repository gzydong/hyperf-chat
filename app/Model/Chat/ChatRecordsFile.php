<?php

declare (strict_types=1);

namespace App\Model\Chat;

use App\Model\BaseModel;

/**
 * 聊天记录(文件消息)数据表模型
 *
 * @property int $id 文件消息ID
 * @property int $record_id 聊天记录ID
 * @property int $user_id 用户ID
 * @property int $file_source 文件上传来源
 * @property int $file_type 文件类型
 * @property int $save_type 文件保存类型
 * @property string $original_name 文件原始名称
 * @property string $file_suffix 文件后缀名
 * @property int $file_size 文件大小
 * @property string $save_dir 文件保存路径
 * @property int $is_delete 是否已删除
 * @property string $created_at 上传时间
 *
 * @package App\Model\Chat
 */
class ChatRecordsFile extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chat_records_file';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'record_id',
        'user_id',
        'file_source',
        'file_type',
        'save_type',
        'original_name',
        'file_suffix',
        'file_size',
        'save_dir',
        'is_delete',
        'created_at'
    ];
    
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'record_id' => 'integer',
        'user_id' => 'integer',
        'file_source' => 'integer',
        'file_type' => 'integer',
        'save_type' => 'integer',
        'file_size' => 'integer',
        'is_delete' => 'integer',
        'created_at' => 'datetime'
    ];
}
