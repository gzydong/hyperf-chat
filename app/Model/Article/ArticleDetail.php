<?php

declare (strict_types=1);

namespace App\Model\Article;

use App\Model\BaseModel;

/**
 * 笔记详情数据表模型
 *
 * @property integer $id 笔记详情ID
 * @property integer $article_id 笔记ID
 * @property string $md_content 笔记MD格式内容
 * @property string $content 笔记html格式内容
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
