<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property int $id
 * @property int $user1
 * @property int $user2
 * @property string $user1_remark
 * @property string $user2_remark
 * @property int $active
 * @property int $status
 * @property string $agree_time
 * @property \Carbon\Carbon $created_at
 */
class UsersFriend extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_friends';

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
    protected $casts = ['id' => 'integer', 'user1' => 'integer', 'user2' => 'integer', 'active' => 'integer', 'status' => 'integer', 'created_at' => 'datetime'];
}
