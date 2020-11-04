<?php

declare (strict_types=1);

namespace App\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $article_id
 * @property string $file_suffix
 * @property int $file_size
 * @property string $save_dir
 * @property string $original_name
 * @property int $status
 * @property \Carbon\Carbon $created_at
 * @property string $deleted_at
 */
class ArticleAnnex extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'article_annex';

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
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'article_id' => 'integer', 'file_size' => 'integer', 'status' => 'integer', 'created_at' => 'datetime'];
}
