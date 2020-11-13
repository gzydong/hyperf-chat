<?php

declare (strict_types=1);

namespace App\Model\Article;

use App\Model\BaseModel;

/**
 * 笔记分类数据表模型
 *
 * @property integer $id 分类ID
 * @property integer $user_id 用户ID
 * @property string $class_name 分类名
 * @property integer $sort 排序[值越小越靠前]
 * @property integer $is_default 默认分类[1:是;0:不是]
 * @property string $created_at 创建时间
 *
 * @package App\Model\Article
 */
class ArticleClass extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'article_class';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'class_name',
        'sort',
        'is_default',
        'created_at',
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
        'is_default' => 'integer',
        'created_at' => 'int'
    ];
}
