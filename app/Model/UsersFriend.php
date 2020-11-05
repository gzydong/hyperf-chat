<?php

declare (strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Db;

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
    protected $fillable = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'user1' => 'integer', 'user2' => 'integer', 'active' => 'integer', 'status' => 'integer', 'created_at' => 'datetime'];

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
            ) tmp_table on tmp_table.uid = users.id  order by tmp_table.rid desc
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
     * @return bool
     */
    public static function isFriend(int $user_id1, int $user_id2)
    {
        // 比较大小交换位置
        if ($user_id1 > $user_id2) {
            [$user_id1, $user_id2] = [$user_id2, $user_id1];
        }

        return self::where('user1', $user_id1)->where('user2', $user_id2)->where('status', 1)->exists();
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
