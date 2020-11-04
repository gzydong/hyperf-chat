<?php

declare (strict_types=1);

namespace App\Model;

/**
 * @property int $id
 * @property int $file_type
 * @property int $user_id
 * @property string $hash_name
 * @property string $original_name
 * @property int $split_index
 * @property int $split_num
 * @property string $save_dir
 * @property string $file_ext
 * @property int $file_size
 * @property int $is_delete
 * @property int $upload_at
 */
class FileSplitUpload extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'file_split_upload';
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
    protected $casts = ['id' => 'integer', 'file_type' => 'integer', 'user_id' => 'integer', 'split_index' => 'integer', 'split_num' => 'integer', 'file_size' => 'integer', 'is_delete' => 'integer', 'upload_at' => 'integer'];
}
