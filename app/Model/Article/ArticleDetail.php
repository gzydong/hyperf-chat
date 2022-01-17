<?php

declare (strict_types=1);

namespace App\Model\Article;

use App\Model\BaseModel;

/**
 * 笔记详情数据表模型
 *
 * @property integer $id         笔记详情ID
 * @property integer $article_id 笔记ID
 * @property string  $md_content 笔记MD格式内容
 * @property string  $content    笔记html格式内容
 *
 * @package App\Model\Article
 */
class ArticleDetail extends BaseModel
{
    protected $table = 'article_detail';

    protected $fillable = [
        'article_id',
        'md_content',
        'content',
    ];

    protected $casts = [
        'article_id' => 'integer'
    ];
}
