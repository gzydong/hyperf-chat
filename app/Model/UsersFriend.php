<?php

declare (strict_types=1);

namespace App\Model;

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
     * 获取用户所有好友
     *
     * @param int $user_id 用户ID
     * @return array
     */
    public static function getUserFriends(int $user_id)
    {
        return UsersFriend::leftJoin('users', 'users.id', '=', 'users_friends.friend_id')
            ->where('user_id', $user_id)->where('users_friends.status', 1)
            ->get([
                'users.id',
                'users.nickname',
                'users.avatar',
                'users.motto',
                'users.gender',
                'users_friends.remark as friend_remark',
            ])->toArray();
    }

    /**
     * 判断用户之间是否存在好友关系
     *
     * @param int  $user_id   用户ID
     * @param int  $friend_id 好友ID
     * @param bool $is_cache  是否允许读取缓存
     * @return bool
     */
    public static function isFriend(int $user_id, int $friend_id, bool $is_cache = false)
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
     * 获取指定用户的所有朋友的用户ID
     *
     * @param int $user_id 指定用户ID
     * @return array
     */
    public static function getFriendIds(int $user_id)
    {
        return UsersFriend::where('user_id', $user_id)->where('status', 1)->pluck('friend_id')->toArray();
    }
}
