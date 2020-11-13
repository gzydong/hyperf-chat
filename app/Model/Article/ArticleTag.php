<?php

declare (strict_types=1);

namespace App\Model\Article;

use App\Model\BaseModel;

/**
 * @property integer $id 标签ID
 * @property integer $user_id 用户ID
 * @property string $tag_name 标签名称
 * @property integer $sort 标签排序
 * @property integer $created_at 创建时间
 */
class ArticleTag extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'article_tags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id','tag_name','sort','created_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'sort' => 'integer',
        'created_at' => 'integer'
    ];
}
