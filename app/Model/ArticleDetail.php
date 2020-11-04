<?php

declare (strict_types=1);

namespace App\Model;

/**
 * @property int $id
 * @property int $article_id
 * @property string $md_content
 * @property string $content
 */
class ArticleDetail extends Model
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
    protected $fillable = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'article_id' => 'integer'];
}
