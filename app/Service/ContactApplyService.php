<?php

namespace App\Service;

use App\Model\UsersFriendsApply;

class ContactApplyService
{
    /**
     * 创建好友申请
     *
     * @param int    $user_id   申请人用户ID
     * @param int    $friend_id 朋友ID
     * @param string $remark    申请备注
     * @return bool
     */
    public function create(int $user_id, int $friend_id, string $remark)
    {
        // 查询最后一次联系人申请
        $result = UsersFriendsApply::where('user_id', $user_id)->where('friend_id', $friend_id)->orderBy('id', 'desc')->first();
        if ($result && $result->status == 0) {
            $result->remarks    = $remark;
            $result->updated_at = date('Y-m-d H:i:s');
            $result->save();
            return true;
        }

        $result = UsersFriendsApply::create([
            'user_id'    => $user_id,
            'friend_id'  => $friend_id,
            'status'     => 0,
            'remarks'    => $remark,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return (bool)$result;
    }

    /**
     * 同意好友申请
     */
    public function accept()
    {

    }

    /**
     * 拒绝好友申请
     */
    public function decline()
    {

    }
}
