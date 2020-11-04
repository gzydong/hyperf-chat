<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property int $id
 * @property int $type
 * @property int $uid
 * @property int $friend_id
 * @property int $group_id
 * @property int $status
 * @property int $is_top
 * @property int $not_disturb
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class UsersChatList extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_chat_list';

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
    protected $casts = ['id' => 'integer', 'type' => 'integer', 'uid' => 'integer', 'friend_id' => 'integer', 'group_id' => 'integer', 'status' => 'integer', 'is_top' => 'integer', 'not_disturb' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
