<?php

declare (strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Db;

/**
 * 表情包收藏数据表模型
 *
 * @property int $id
 * @property int $user1 用户1ID
 * @property int $user2 用户2ID
 * @property string $user1_remark 用户1好友备注
 * @property string $user2_remark 用户2好友备注
 * @property int $active 主动邀请方[1:user1;2:user2;]
 * @property int $status 好友状态[1:好友状态;0:已解除好友关系]
 * @property string $agree_time 成为好友时间
 * @property string $created_at 创建时间
 *
 * @package App\Model
 */
class UsersFriend extends BaseModel
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
    protected $fillable = [
        'user1',
        'user2',
        'user1_remark',
        'user2_remark',
        'active',
        'status',
        'agree_time',
        'created_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'user1' => 'integer',
        'user2' => 'integer',
        'active' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime'
    ];

    /**
     * 获取用户所有好友
     *
     * @param int $uid 用户ID
     * @return mixed
     */
    public static function getUserFriends(int $uid)
    {
        $prefix = config('databases.default.prefix');
        $sql = <<<SQL
            SELECT users.id,users.nickname,users.avatar,users.motto,users.gender,tmp_table.friend_remark from {$prefix}users users
            INNER join
            (
              SELECT id as rid,user2 as uid,user1_remark as friend_remark from {$prefix}users_friends where user1 = {$uid} and `status` = 1
                UNION all 
              SELECT id as rid,user1 as uid,user2_remark as friend_remark from {$prefix}users_friends where user2 = {$uid} and `status` = 1
            ) tmp_table on tmp_table.uid = users.id
SQL;

        $rows = Db::select($sql);

        array_walk($rows, function (&$item) {
            $item = (array)$item;
        });

        return $rows;
    }

    /**
     * 判断用户之间是否存在好友关系
     *
     * @param int $user_id1 用户1
     * @param int $user_id2 用户2
     * @param bool $cache 是否读取缓存
     * @return bool
     */
    public static function isFriend(int $user_id1, int $user_id2, bool $cache = false)
    {
        // 比较大小交换位置
        if ($user_id1 > $user_id2) {
            [$user_id1, $user_id2] = [$user_id2, $user_id1];
        }

        $cacheKey = "good_friends:{$user_id1}_$user_id2";
        if ($cache && redis()->get($cacheKey)) {
            return true;
        }

        $isTrue = self::where('user1', $user_id1)->where('user2', $user_id2)->where('status', 1)->exists();
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
        $prefix = config('databases.default.prefix');
        $sql = "SELECT user2 as uid from {$prefix}users_friends where user1 = {$user_id} and `status` = 1 UNION all SELECT user1 as uid from {$prefix}users_friends where user2 = {$user_id} and `status` = 1";
        return array_map(function ($item) {
            return $item->uid;
        }, Db::select($sql));
    }
}
