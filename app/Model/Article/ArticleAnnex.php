<?php

declare (strict_types=1);

namespace App\Model\Article;

use App\Model\BaseModel;

/**
 * 笔记附件数据表模型
 *
 * @property int    $id            自增ID
 * @property int    $user_id       用户ID
 * @property int    $article_id    笔记ID
 * @property int    $drive         文件驱动
 * @property string $suffix        文件后缀名
 * @property int    $size          文件大小
 * @property string $path          文件地址
 * @property string $original_name 文件原名
 * @property int    $status        文件状态
 * @property string $created_at    创建时间
 * @property string $updated_at    更新时间
 * @property string $deleted_at    删除时间
 *
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
