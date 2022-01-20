<?php

declare (strict_types=1);

namespace App\Model\Emoticon;

use App\Model\BaseModel;

/**
 * 表情包收藏数据表模型
 *
 * @property int    $id           收藏ID
 * @property int    $user_id      用户ID
 * @property string $emoticon_ids 表情包ID，多个用英文逗号拼接
 * @package App\Model
 */
class UsersEmoticon extends BaseModel
{
    protected $table = 'users_emoticon';

    protected $fillable = [
        'user_id',
        'emoticon_ids'
    ];

    protected $casts = [
        'user_id' => 'integer'
    ];

    /**
     * @param $value
     * @return false|string[]
     */
    public function getEmoticonIdsAttribute($value)
    {
        return explode(',', $value);
    }
}
