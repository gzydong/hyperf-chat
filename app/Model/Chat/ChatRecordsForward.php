<?php

declare (strict_types=1);

namespace App\Model\Chat;

use App\Model\BaseModel;

/**
 * @property int $id
 * @property int $record_id
 * @property int $user_id
 * @property string $records_id
 * @property string $text
 * @property \Carbon\Carbon $created_at
 */
class ChatRecordsForward extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chat_records_forward';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'record_id', 'user_id', 'created_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'record_id' => 'integer', 'user_id' => 'integer', 'created_at' => 'datetime'];
}
