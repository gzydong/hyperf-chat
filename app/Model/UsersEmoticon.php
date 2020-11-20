<?php

declare (strict_types=1);

namespace App\Model;

/**
 * 表情包收藏数据表模型
 *
 * @property int $id 收藏ID
 * @property int $user_id 用户ID
 * @property string $emoticon_ids 表情包ID，多个用英文逗号拼接
 *
 * @package App\Model
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
    protected $fillable = [
        'user_id',
        'emoticon_ids'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer'
    ];

    /**
     *
     * @param  string $value
     * @return string
     */
    public function getEmoticonIdsAttribute($value)
    {
        return explode(',', $value);
    }
}
