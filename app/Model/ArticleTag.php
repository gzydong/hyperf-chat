<?php

declare (strict_types=1);

namespace App\Model;


/**
 * @property int $id
 * @property int $user_id
 * @property string $tag_name
 * @property int $sort
 * @property \Carbon\Carbon $created_at
 */
class ArticleTag extends Model
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
    protected $fillable = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'sort' => 'integer', 'created_at' => 'datetime'];
}
