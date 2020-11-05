<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $emoticon_ids
 */
class UsersEmoticon extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_emoticon';

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
    protected $casts = ['id' => 'integer', 'user_id' => 'integer'];
}
