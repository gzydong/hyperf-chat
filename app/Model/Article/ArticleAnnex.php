<?php

declare (strict_types=1);

namespace App\Model\Article;

use App\Model\BaseModel;

/**
 * 笔记附件数据表模型
 *
 * @property integer $id            笔记附件ID
 * @property integer $user_id       用户ID
 * @property integer $article_id    笔记ID
 * @property integer $drive         文件驱动
 * @property string  $file_suffix   文件后缀名
 * @property int     $file_size     文件大小
 * @property string  $path          文件相对路径
 * @property string  $original_name 文件原名
 * @property integer $status        文件状态
 * @property string  $created_at    上传时间
 * @property string  $deleted_at    删除时间
 * @package App\Model\Article
 */
class ArticleAnnex extends BaseModel
{
    protected $table = 'article_annex';

    protected $fillable = [
        'user_id',
        'article_id',
        'drive',
        'suffix',
        'size',
        'path',
        'original_name',
        'status',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'user_id'    => 'integer',
        'article_id' => 'integer',
        'size'       => 'integer',
        'status'     => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
