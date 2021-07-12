<?php

declare (strict_types=1);

namespace App\Model\Article;

use App\Model\BaseModel;

/**
 * 笔记标签数据表模型
 *
 * @property integer $id         标签ID
 * @property integer $user_id    用户ID
 * @property string  $tag_name   标签名称
 * @property integer $sort       标签排序
 * @property integer $created_at 创建时间
 */
class ArticleTag extends BaseModel
{
    protected $table = 'article_tags';

    protected $fillable = [
        'user_id',
        'tag_name',
        'sort',
        'created_at'
    ];

    protected $casts = [
        'user_id'    => 'integer',
        'sort'       => 'integer',
        'created_at' => 'integer'
    ];
}
