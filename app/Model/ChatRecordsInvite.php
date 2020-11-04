<?php

declare (strict_types=1);

namespace App\Model;

/**
 * @property int $id
 * @property int $record_id
 * @property int $type
 * @property int $operate_user_id
 * @property string $user_ids
 */
class ChatRecordsInvite extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chat_records_invite';

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
    protected $casts = ['id' => 'integer', 'record_id' => 'integer', 'type' => 'integer', 'operate_user_id' => 'integer'];
}
