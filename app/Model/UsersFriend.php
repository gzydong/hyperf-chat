<?php

declare (strict_types=1);

namespace App\Model;

use App\Cache\FriendRemark;

/**
 * 表情包收藏数据表模型
 *
 * @property int    $id
 * @property int    $user_id          用户ID
 * @property int    $friend_id        好友ID
 * @property string $remark           好友备注
 * @property int    $status           好友状态
 * @property string $created_at       创建时间
 * @property string $updated_at       更新时间
 * @package App\Model
 */
class UsersFriend extends BaseModel
{
    protected $table = 'users_friends';

    protected $fillable = [
        'user_id',
        'friend_id',
        'status',
        'remark',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id'         => 'integer',
        'user_id'    => 'integer',
        'friend_id'  => 'integer',
        'status'     => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 判断用户之间是否存在好友关系
     *
     * @param int  $user_id   用户ID
     * @param int  $friend_id 好友ID
     * @param bool $is_cache  是否允许读取缓存
     * @param bool $is_mutual 相互互为好友
     * @return bool
     */
    public static function isFriend(int $user_id, int $friend_id, bool $is_cache = false, $is_mutual = false)
    {
        $cacheKey = "good_friends:{$user_id}_{$friend_id}";
        if ($is_cache && redis()->get($cacheKey)) {
            return true;
        }

        $isTrue = self::query()->where('user_id', $user_id)->where('friend_id', $friend_id)->where('status', 1)->exists();
        if ($isTrue) {
            redis()->setex($cacheKey, 60 * 5, 1);
        }

        return $isTrue;
    }

    /**
     * 获取好友备注
     *
     * @param int $user_id   用户ID
     * @param int $friend_id 好友ID
     * @return string
     */
    public static function getFriendRemark(int $user_id, int $friend_id)
    {
        $remark = FriendRemark::getInstance()->read($user_id, $friend_id);
        if ($remark) return $remark;

        $remark = UsersFriend::where('user_id', $user_id)->where('friend_id', $friend_id)->value('remark');
        if ($remark) FriendRemark::getInstance()->save($user_id, $friend_id, $remark);

        return (string)$remark;
    }
}
