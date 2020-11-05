<?php

declare (strict_types=1);

namespace App\Model\Article;

use App\Model\BaseModel;

/**
 * 笔记详情数据表模型
 *
 * @property int $id
 * @property int $article_id
 * @property string $md_content
 * @property string $content
 *
 * @package App\Model\Article
 */
class ArticleDetail extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'article_detail';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'article_id',
        'md_content',
        'content',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'article_id' => 'integer'
    ];
}
