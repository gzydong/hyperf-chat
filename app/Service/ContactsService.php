<?php
/**
 * This is my open source code, please do not use it for commercial applications.
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author Yuandong<837215079@qq.com>
 * @link   https://github.com/gzydong/hyperf-chat
 */

namespace App\Service;

use App\Model\User;
use App\Model\UsersFriend;
use App\Model\UsersFriendsApply;
use App\Traits\PagingTrait;
use Hyperf\DbConnection\Db;
use Exception;

/**
 * ContactsService
 * 注：联系人服务层
 *
 * @package App\Service
 */
class ContactsService extends BaseService
{
    use PagingTrait;

    /**
     * 获取联系人列表
     *
     * @param int $user_id 用户ID
     * @return array
     */
    public function getContacts(int $user_id): array
    {
        $prefix = config('databases.default.prefix');
        $sql    = <<<SQL
            SELECT users.id,users.nickname,users.avatar,users.motto,users.gender,tmp_table.friend_remark from {$prefix}users users
            INNER join
            (
              SELECT id as rid,user2 as uid,user1_remark as friend_remark from {$prefix}users_friends where user1 = {$user_id} and `status` = 1
                UNION all 
              SELECT id as rid,user1 as uid,user2_remark as friend_remark from {$prefix}users_friends where user2 = {$user_id} and `status` = 1
            ) tmp_table on tmp_table.uid = users.id
SQL;

        $rows = Db::select($sql);

        array_walk($rows, function (&$item) {
            $item = (array)$item;
        });

        return $rows;
    }

    /**
     * 添加联系人/申请
     *
     * @param int    $user_id   用户ID
     * @param int    $friend_id 好友ID
     * @param string $remarks   申请备注
     * @return bool
     */
    public function addContact(int $user_id, int $friend_id, string $remarks): bool
    {
        // 判断是否是好友关系
        if (UsersFriend::isFriend($user_id, $friend_id)) return true;

        // 查询最后一次联系人申请
        $result = UsersFriendsApply::where('user_id', $user_id)
            ->where('friend_id', $friend_id)
            ->orderBy('id', 'desc')->first();

        if ($result && $result->status == 0) {
            $result->remarks    = $remarks;
            $result->updated_at = date('Y-m-d H:i:s');
            $result->save();
            return true;
        } else {
            $result = UsersFriendsApply::create([
                'user_id'    => $user_id,
                'friend_id'  => $friend_id,
                'status'     => 0,
                'remarks'    => $remarks,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return (bool)$result;
        }
    }

    /**
     * 删除联系人
     *
     * @param int $user_id   用户ID
     * @param int $friend_id 好友ID
     * @return bool
     */
    public function deleteContact(int $user_id, int $friend_id): bool
    {
        if (!UsersFriend::isFriend($user_id, $friend_id)) {
            return false;
        }

        $data = ['status' => 0];

        // 用户ID比大小交换位置
        if ($user_id > $friend_id) {
            [$user_id, $friend_id] = [$friend_id, $user_id];
        }

        return (bool)UsersFriend::where('user1', $user_id)->where('user2', $friend_id)->update($data);
    }

    /**
     * 同意添加联系人 / 联系人申请
     *
     * @param int    $user_id  用户ID
     * @param int    $apply_id 联系人申请ID
     * @param string $remarks  联系人备注名称
     * @return bool
     */
    public function acceptInvitation(int $user_id, int $apply_id, string $remarks = ''): bool
    {
        $info = UsersFriendsApply::where('id', $apply_id)
            ->where('friend_id', $user_id)
            ->where('status', 0)
            ->orderBy('id', 'desc')
            ->first(['user_id', 'friend_id']);

        if (!$info) return false;

        Db::beginTransaction();
        try {
            $res = UsersFriendsApply::where('id', $apply_id)->update(['status' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
            if (!$res) {
                throw new Exception('更新好友申请表信息失败');
            }

            // 判断大小 交换 user1,user2 的位置
            [$user1, $user2] = $info->user_id < $info->friend_id ? [$info->user_id, $info->friend_id] : [$info->friend_id, $info->user_id];

            // 查询是否存在好友记录
            $friendResult = UsersFriend::where([
                ['user1', '=', $user1],
                ['user2', '=', $user2]
            ])->first(['id', 'user1', 'user2', 'active', 'status']);

            if ($friendResult) {
                $active = ($friendResult->user1 == $info->user_id && $friendResult->user2 == $info->friend_id) ? 1 : 2;
                UsersFriend::where('id', $friendResult->id)->update(['active' => $active, 'status' => 1]);
            } else {
                //好友昵称
                $friend_nickname = User::where('id', $info->friend_id)->value('nickname');

                UsersFriend::create([
                    'user1'        => $user1,
                    'user2'        => $user2,
                    'user1_remark' => $user1 == $user_id ? $remarks : $friend_nickname,
                    'user2_remark' => $user2 == $user_id ? $remarks : $friend_nickname,
                    'active'       => $user1 == $user_id ? 2 : 1,
                    'status'       => 1,
                    'agree_time'   => date('Y-m-d H:i:s'),
                    'created_at'   => date('Y-m-d H:i:s')
                ]);
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return false;
        }

        return true;
    }

    /**
     * 拒绝添加联系人 / 联系人申请
     *
     * @param int    $user_id  用户ID
     * @param int    $apply_id 联系人申请ID
     * @param string $remarks  拒绝申请备注信息
     * @return bool
     */
    public function declineInvitation(int $user_id, int $apply_id, string $remarks = ''): bool
    {
        return (bool)UsersFriendsApply::where([
            ['id', '=', $apply_id],
            ['user_id', '=', $user_id],
            ['status', '=', 2],
        ])->update([
            'status'     => 2,
            'remarks'    => $remarks,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 编辑联系人备注
     *
     * @param int    $user_id   用户ID
     * @param int    $friend_id 朋友ID
     * @param string $remarks   好友备注名称
     * @return bool
     */
    public function editContactRemark(int $user_id, int $friend_id, string $remarks): bool
    {
        $data = [];
        if ($user_id > $friend_id) {
            [$user_id, $friend_id] = [$friend_id, $user_id];
            $data['user2_remark'] = $remarks;
        } else {
            $data['user1_remark'] = $remarks;
        }

        return (bool)UsersFriend::where('user1', $user_id)->where('user2', $friend_id)->update($data);
    }

    /**
     * 搜索联系人
     *
     * @param string $mobile 用户手机号/登录账号
     * @return array
     */
    public function findContact(string $mobile): array
    {
        $user = User::where('mobile', $mobile)->first(['id', 'nickname', 'mobile', 'avatar', 'gender']);

        return $user ? $user->toArray() : [];
    }

    /**
     * 获取联系人申请记录
     *
     * @param int $user_id   用户ID
     * @param int $page      当前分页
     * @param int $page_size 分页大小
     * @return array
     */
    public function getContactApplyRecords(int $user_id, $page = 1, $page_size = 30): array
    {
        $rowsSqlObj = UsersFriendsApply::select([
            'users_friends_apply.id',
            'users_friends_apply.status',
            'users_friends_apply.remarks',
            'users.nickname',
            'users.avatar',
            'users.mobile',
            'users_friends_apply.user_id',
            'users_friends_apply.friend_id',
            'users_friends_apply.created_at'
        ]);

        $rowsSqlObj->leftJoin('users', 'users.id', '=', 'users_friends_apply.user_id');
        $rowsSqlObj->where('users_friends_apply.friend_id', $user_id);

        $count = $rowsSqlObj->count();
        $rows  = [];
        if ($count > 0) {
            $rows = $rowsSqlObj->orderBy('users_friends_apply.id', 'desc')->forPage($page, $page_size)->get()->toArray();
        }

        return $this->getPagingRows($rows, $count, $page, $page_size);
    }

    /**
     * 删除联系人申请记录
     *
     * @param int $user_id  用户ID
     * @param int $apply_id 联系人好友申请ID
     * @return bool
     * @throws Exception
     */
    public function delContactApplyRecord(int $user_id, int $apply_id): bool
    {
        return (bool)UsersFriendsApply::where('id', $apply_id)->where('friend_id', $user_id)->delete();
    }
}
