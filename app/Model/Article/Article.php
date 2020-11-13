<?php

declare (strict_types=1);

namespace App\Model\Article;

use App\Model\BaseModel;

/**
 * 笔记数据表模型
 *
 * @property integer $id 笔记ID
 * @property integer $user_id 用户ID
 * @property integer $class_id 分类ID
 * @property string $tags_id 笔记标签ID
 * @property string $title 笔记标题
 * @property string $abstract 笔记摘要
 * @property string $image 笔记头图
 * @property integer $is_asterisk 是否标记星号
 * @property integer $status 笔记状态
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $deleted_at 删除时间
 *
 * @package App\Model\Article
 */
class Article extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'article';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'class_id',
        'title',
        'abstract',
        'image',
        'is_asterisk',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'class_id' => 'integer',
        'is_asterisk' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * 关联笔记详细表(一对一关系)
     *
     * @return \Hyperf\Database\Model\Relations\HasOne
     */
    public function detail()
    {
        return $this->hasOne(ArticleDetail::class, 'article_id', 'id');
    }

    /**
     * 关联笔记附件信息表(一对多关系)
     *
     * @return \Hyperf\Database\Model\Relations\HasMany
     */
    public function annexs()
    {
        return $this->hasMany(ArticleAnnex::class, 'article_id', 'id');
    }
}
