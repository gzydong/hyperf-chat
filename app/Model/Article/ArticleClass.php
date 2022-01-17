<?php

declare (strict_types=1);

namespace App\Model\Article;

use App\Model\BaseModel;

/**
 * 笔记分类数据表模型
 *
 * @property integer $id         分类ID
 * @property integer $user_id    用户ID
 * @property string  $class_name 分类名
 * @property integer $sort       排序[值越小越靠前]
 * @property integer $is_default 默认分类[1:是;0:不是]
 * @property string  $created_at 创建时间
 * @property string  $updated_at 更新时间
 *
 * @package App\Model\Article
 */
class ArticleClass extends BaseModel
{
    protected $table = 'article_class';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'class_name',
        'sort',
        'is_default',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'user_id'    => 'integer',
        'sort'       => 'integer',
        'is_default' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
