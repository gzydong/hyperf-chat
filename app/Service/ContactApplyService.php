<?php

namespace App\Service;

use App\Model\User;
use App\Model\UsersFriend;
use App\Model\UsersFriendsApply;
use App\Traits\PagingTrait;
use Hyperf\DbConnection\Db;

class ContactApplyService
{
    use PagingTrait;

    /**
     * 创建好友申请
     *
     * @param int    $user_id   用户ID
     * @param int    $friend_id 朋友ID
     * @param string $remark    申请备注
     * @return array
     */
    public function create(int $user_id, int $friend_id, string $remark)
    {
        $result = UsersFriendsApply::create([
            'user_id'    => $user_id,
            'friend_id'  => $friend_id,
            'status'     => 0,
            'remark'     => $remark,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$result) {
            return [false, $result];
        }

        return [true, $result];
    }

    /**
     * 同意好友申请
     *
     * @param int $user_id  用户ID
     * @param int $apply_id 申请记录ID
     */
    public function accept(int $user_id, int $apply_id, string $remarks = '')
    {
        $info = UsersFriendsApply::where('id', $apply_id)->first();
        if (!$info || $info->friend_id != $user_id) {
            return false;
        }

        Db::beginTransaction();
        try {
            $info->status = 1;
            $info->save();

            UsersFriend::updateOrCreate([
                'user_id'   => $info->user_id,
                'friend_id' => $info->friend_id,
            ], [
                'status' => 1,
                'remark' => $remarks,
            ]);

            UsersFriend::updateOrCreate([
                'user_id'   => $info->friend_id,
                'friend_id' => $info->user_id,
            ], [
                'status' => 1,
                'remark' => User::where('id', $info->user_id)->value('nickname'),
            ]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            echo $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * 拒绝好友申请
     *
     * @param int $user_id  用户ID
     * @param int $apply_id 申请记录ID
     */
    public function decline(int $user_id, int $apply_id, string $reason = '')
    {
        return (bool)UsersFriendsApply::where('id', $apply_id)->where('friend_id', $user_id)->update([
            'status'     => 2,
            'reason'     => $reason,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 删除好友申请
     *
     * @param int $user_id
     * @param int $apply_id
     * @return bool
     */
    public function delete(int $user_id, int $apply_id)
    {
        return (bool)UsersFriendsApply::where('id', $apply_id)->where('friend_id', $user_id)->delete();
    }

    /**
     * 获取联系人申请记录
     *
     * @param int $user_id   用户ID
     * @param int $page      当前分页
     * @param int $page_size 分页大小
     * @return array
     */
    public function getApplyRecords(int $user_id, $page = 1, $page_size = 30): array
    {
        $rowsSqlObj = UsersFriendsApply::select([
            'users_friends_apply.id',
            'users_friends_apply.status',
            'users_friends_apply.remark',
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
}
