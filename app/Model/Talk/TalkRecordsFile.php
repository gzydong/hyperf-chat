<?php

declare (strict_types=1);

namespace App\Model\Talk;

use App\Model\BaseModel;

/**
 * 聊天记录(文件消息)数据表模型
 *
 * @property int    $id            文件消息ID
 * @property int    $record_id     聊天记录ID
 * @property int    $user_id       用户ID
 * @property int    $source        文件上传来源
 * @property int    $type          文件类型
 * @property int    $drive         文件保存类型
 * @property string $original_name 文件原始名称
 * @property string $suffix        文件后缀名
 * @property int    $size          文件大小
 * @property string $path          文件保存路径
 * @property string $created_at    上传时间
 * @package App\Model\Chat
 */
class TalkRecordsFile extends BaseModel
{
    protected $table = 'talk_records_file';

    protected $fillable = [
        'record_id',
        'user_id',
        'source',
        'type',
        'drive',
        'original_name',
        'suffix',
        'size',
        'path',
        'url',
        'created_at'
    ];

    protected $casts = [
        'record_id'  => 'integer',
        'user_id'    => 'integer',
        'source'     => 'integer',
        'type'       => 'integer',
        'drive'      => 'integer',
        'size'       => 'integer',
        'created_at' => 'datetime'
    ];
}
