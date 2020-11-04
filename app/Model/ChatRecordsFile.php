<?php

declare (strict_types=1);

namespace App\Model;

/**
 * @property int $id
 * @property int $record_id
 * @property int $user_id
 * @property int $file_source
 * @property int $file_type
 * @property int $save_type
 * @property string $original_name
 * @property string $file_suffix
 * @property int $file_size
 * @property string $save_dir
 * @property int $is_delete
 * @property \Carbon\Carbon $created_at
 */
class ChatRecordsFile extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chat_records_file';

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
    protected $casts = ['id' => 'integer', 'record_id' => 'integer', 'user_id' => 'integer', 'file_source' => 'integer', 'file_type' => 'integer', 'save_type' => 'integer', 'file_size' => 'integer', 'is_delete' => 'integer', 'created_at' => 'datetime'];
}
