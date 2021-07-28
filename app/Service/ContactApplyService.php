<?php

namespace App\Service;

use App\Cache\FriendApply;
use App\Constants\TalkEventConstant;
use App\Event\TalkEvent;
use App\Model\User;
use App\Model\UsersFriend;
use App\Model\UsersFriendApply;
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
     * @return bool
     */
    public function create(int $user_id, int $friend_id, string $remark)
    {
        $result = UsersFriendApply::where([
            ['user_id', '=', $user_id],
            ['friend_id', '=', $friend_id],
        ])->orderByDesc('id')->first();

        if (!$result) {
            $result = UsersFriendApply::create([
                'user_id'    => $user_id,
                'friend_id'  => $friend_id,
                'remark'     => $remark,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $result->remark     = $remark;
            $result->created_at = date('Y-m-d H:i:s');
            $result->save();
        }

        // 好友申请未读消息数自增
        FriendApply::getInstance()->incr($friend_id, 1);

        // 判断对方是否在线。如果在线发送消息通知
        $isOnline = di()->get(SocketClientService::class)->isOnlineAll($friend_id);
        if ($isOnline) {
            event()->dispatch(new TalkEvent(TalkEventConstant::EVENT_FRIEND_APPLY, [
                'apply_id' => $result->id,
                'type'     => 1,
            ]));
        }

        return true;
    }

    /**
     * 同意好友申请
     *
     * @param int $user_id  用户ID
     * @param int $apply_id 申请记录ID
     */
    public function accept(int $user_id, int $apply_id, string $remarks = '')
    {
        $info = UsersFriendApply::where('id', $apply_id)->first();
        if (!$info || $info->friend_id != $user_id) {
            return false;
        }

        Db::beginTransaction();
        try {
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

            $info->delete();

            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            return false;
        }

        // 判断对方是否在线。如果在线发送消息通知
        $isOnline = di()->get(SocketClientService::class)->isOnlineAll($info->user_id);
        if ($isOnline) {
            event()->dispatch(new TalkEvent(TalkEventConstant::EVENT_FRIEND_APPLY, [
                'apply_id' => $apply_id,
                'type'     => 2,
            ]));
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
        $result = UsersFriendApply::where('id', $apply_id)->where('friend_id', $user_id)->delete();

        if (!$result) return false;

        // todo 做聊天记录的推送

        return true;
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
        $rowsSqlObj = UsersFriendApply::select([
            'users_friends_apply.id',
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
