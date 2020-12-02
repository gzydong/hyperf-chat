<?php

namespace App\Service;

use App\Model\User;
use App\Model\UsersFriend;
use App\Model\UsersFriendsApply;
use App\Traits\PagingTrait;
use Hyperf\DbConnection\Db;

class FriendService extends BaseService
{
    use PagingTrait;

    /**
     * 创建好友的申请
     *
     * @param int $user_id 用户ID
     * @param int $friend_id 好友ID
     * @param string $remarks 好友申请备注
     * @return bool
     */
    public function addFriendApply(int $user_id, int $friend_id, string $remarks)
    {
        // 判断是否是好友关系
        if (UsersFriend::isFriend($user_id, $friend_id)) {
            return true;
        }

        $result = UsersFriendsApply::where('user_id', $user_id)->where('friend_id', $friend_id)->orderBy('id', 'desc')->first();
        if (!$result) {
            $result = UsersFriendsApply::create([
                'user_id' => $user_id,
                'friend_id' => $friend_id,
                'status' => 0,
                'remarks' => $remarks,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return $result ? true : false;
        } else if ($result->status == 0) {
            $result->remarks = $remarks;
            $result->updated_at = date('Y-m-d H:i:s');
            $result->save();

            return true;
        }

        return false;
    }

    /**
     * 删除好友申请记录
     *
     * @param int $user_id 用户ID
     * @param int $apply_id 好友申请ID
     * @return mixed
     */
    public function delFriendApply(int $user_id, int $apply_id)
    {
        return (bool)UsersFriendsApply::where('id', $apply_id)->where('friend_id', $user_id)->delete();
    }

    /**
     * 处理好友的申请
     *
     * @param int $user_id 当前用户ID
     * @param int $apply_id 申请记录ID
     * @param string $remarks 备注信息
     * @return bool
     */
    public function handleFriendApply(int $user_id, int $apply_id, $remarks = '')
    {
        $info = UsersFriendsApply::where('id', $apply_id)->where('friend_id', $user_id)->where('status', 0)->orderBy('id', 'desc')->first(['user_id', 'friend_id']);
        if (!$info) {
            return false;
        }

        Db::beginTransaction();
        try {
            $res = UsersFriendsApply::where('id', $apply_id)->update(['status' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
            if (!$res) {
                throw new \Exception('更新好友申请表信息失败');
            }

            $user1 = $info->user_id;
            $user2 = $info->friend_id;
            if ($info->user_id > $info->friend_id) {
                [$user1, $user2] = [$info->friend_id, $info->user_id];
            }

            //查询是否存在好友记录
            $friendResult = UsersFriend::select('id', 'user1', 'user2', 'active', 'status')->where('user1', '=', $user1)->where('user2', '=', $user2)->first();
            if ($friendResult) {
                $active = ($friendResult->user1 == $info->user_id && $friendResult->user2 == $info->friend_id) ? 1 : 2;
                if (!UsersFriend::where('id', $friendResult->id)->update(['active' => $active, 'status' => 1])) {
                    throw new \Exception('更新好友关系信息失败');
                }
            } else {
                //好友昵称
                $friend_nickname = User::where('id', $info->friend_id)->value('nickname');
                $insRes = UsersFriend::create([
                    'user1' => $user1,
                    'user2' => $user2,
                    'user1_remark' => $user1 == $user_id ? $remarks : $friend_nickname,
                    'user2_remark' => $user2 == $user_id ? $remarks : $friend_nickname,
                    'active' => $user1 == $user_id ? 2 : 1,
                    'status' => 1,
                    'agree_time' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                if (!$insRes) {
                    throw new \Exception('创建好友关系失败');
                }
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            return false;
        }

        return true;
    }

    /**
     * 解除好友关系
     *
     * @param int $user_id 用户ID
     * @param int $friend_id 好友ID
     * @return bool
     */
    public function removeFriend(int $user_id, int $friend_id)
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
     * 获取用户好友申请记录
     *
     * @param int $user_id 用户ID
     * @param int $page 分页数
     * @param int $page_size 分页大小
     * @return array
     */
    public function findApplyRecords(int $user_id, $page = 1, $page_size = 30)
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
        $rows = [];
        if ($count > 0) {
            $rows = $rowsSqlObj->orderBy('users_friends_apply.id', 'desc')->forPage($page, $page_size)->get()->toArray();
        }

        return $this->getPagingRows($rows, $count, $page, $page_size);
    }

    /**
     * 编辑好友备注信息
     *
     * @param int $user_id 用户ID
     * @param int $friend_id 朋友ID
     * @param string $remarks 好友备注名称
     * @return bool
     */
    public function editFriendRemark(int $user_id, int $friend_id, string $remarks)
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
}
