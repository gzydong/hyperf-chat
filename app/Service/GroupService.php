<?php

namespace App\Service;

use App\Cache\LastMsgCache;
use App\Model\Chat\ChatRecord;
use App\Model\Chat\ChatRecordsInvite;
use App\Model\Group\UsersGroup;
use App\Model\Group\UsersGroupMember;
use App\Model\UsersChatList;
use Hyperf\DbConnection\Db;
use Exception;

/**
 * Class GroupService
 * @package App\Service
 */
class GroupService extends BaseService
{
    /**
     * 创建群组
     *
     * @param int $user_id 用户ID
     * @param array $group_info 群聊名称
     * @param array $friend_ids 好友的用户ID
     * @return array
     */
    public function create(int $user_id, array $group_info, $friend_ids = [])
    {
        $friend_ids[] = $user_id;
        $groupMember = [];
        $chatList = [];

        Db::beginTransaction();
        try {
            $insRes = UsersGroup::create([
                'user_id' => $user_id,
                'group_name' => $group_info['name'],
                'avatar' => $group_info['avatar'],
                'group_profile' => $group_info['profile'],
                'status' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if (!$insRes) {
                throw new Exception('创建群失败');
            }

            foreach ($friend_ids as $k => $uid) {
                $groupMember[] = [
                    'group_id' => $insRes->id,
                    'user_id' => $uid,
                    'group_owner' => $user_id == $uid ? 1 : 0,
                    'status' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $chatList[] = [
                    'type' => 2,
                    'uid' => $uid,
                    'friend_id' => 0,
                    'group_id' => $insRes->id,
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }

            if (!Db::table('users_group_member')->insert($groupMember)) {
                throw new Exception('创建群成员信息失败');
            }

            if (!Db::table('users_chat_list')->insert($chatList)) {
                throw new Exception('创建群成员的聊天列表失败');
            }

            $result = ChatRecord::create([
                'msg_type' => 3,
                'source' => 2,
                'user_id' => 0,
                'receive_id' => $insRes->id,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if (!$result) {
                throw new Exception('创建群成员的聊天列表失败');
            }

            ChatRecordsInvite::create([
                'record_id' => $result->id,
                'type' => 1,
                'operate_user_id' => $user_id,
                'user_ids' => implode(',', $friend_ids)
            ]);

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            logger()->error($e);
            return [false, 0];
        }

        // 设置群聊消息缓存
        LastMsgCache::set(['created_at' => date('Y-m-d H:i:s'), 'text' => '入群通知'], $insRes->id, 0);

        return [true, ['record_id' => $result->id, 'group_id' => $insRes->id]];
    }

    /**
     * 解散群组
     *
     * @param int $group_id 群ID
     * @param int $user_id 用户ID
     * @return bool
     */
    public function dismiss(int $group_id, int $user_id)
    {
        if (!UsersGroup::where('id', $group_id)->where('status', 0)->exists()) {
            return false;
        }

        //判断执行者是否属于群主
        if (!UsersGroup::isManager($user_id, $group_id)) {
            return false;
        }

        Db::beginTransaction();
        try {
            UsersGroup::where('id', $group_id)->update(['status' => 1]);
            UsersGroupMember::where('group_id', $group_id)->update(['status' => 1]);
            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return false;
        }

        return true;
    }

    /**
     * 邀请加入群组
     *
     * @param int $user_id 用户ID
     * @param int $group_id 聊天群ID
     * @param array $friend_ids 被邀请的用户ID
     * @return array
     */
    public function invite(int $user_id, int $group_id, $friend_ids = [])
    {
        $info = UsersGroupMember::select(['id', 'status'])->where('group_id', $group_id)->where('user_id', $user_id)->first();

        //判断主动邀请方是否属于聊天群成员
        if (!$info && $info->status == 1) {
            return [false, 0];
        }

        if (empty($friend_ids)) {
            return [false, 0];
        }

        $updateArr = $insertArr = $updateArr1 = $insertArr1 = [];

        $members = UsersGroupMember::where('group_id', $group_id)->whereIn('user_id', $friend_ids)->get(['id', 'user_id', 'status'])->keyBy('user_id')->toArray();
        $chatArr = UsersChatList::where('group_id', $group_id)->whereIn('uid', $friend_ids)->get(['id', 'uid', 'status'])->keyBy('uid')->toArray();

        foreach ($friend_ids as $uid) {
            if (!isset($members[$uid])) {//存在聊天群成员记录
                $insertArr[] = ['group_id' => $group_id, 'user_id' => $uid, 'group_owner' => 0, 'status' => 0, 'created_at' => date('Y-m-d H:i:s')];
            } else if ($members[$uid]['status'] == 1) {
                $updateArr[] = $members[$uid]['id'];
            }

            if (!isset($chatArr[$uid])) {
                $insertArr1[] = ['type' => 2, 'uid' => $uid, 'friend_id' => 0, 'group_id' => $group_id, 'status' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
            } else if ($chatArr[$uid]['status'] == 0) {
                $updateArr1[] = $chatArr[$uid]['id'];
            }
        }

        try {
            if ($updateArr) {
                UsersGroupMember::whereIn('id', $updateArr)->update(['status' => 0]);
            }

            if ($insertArr) {
                Db::table('users_group_member')->insert($insertArr);
            }

            if ($updateArr1) {
                UsersChatList::whereIn('id', $updateArr1)->update(['status' => 1, 'created_at' => date('Y-m-d H:i:s')]);
            }

            if ($insertArr1) {
                Db::table('users_chat_list')->insert($insertArr1);
            }

            $result = ChatRecord::create([
                'msg_type' => 3,
                'source' => 2,
                'user_id' => 0,
                'receive_id' => $group_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if (!$result) throw new Exception('添加群通知记录失败1');

            $result2 = ChatRecordsInvite::create([
                'record_id' => $result->id,
                'type' => 1,
                'operate_user_id' => $user_id,
                'user_ids' => implode(',', $friend_ids)
            ]);

            if (!$result2) throw new Exception('添加群通知记录失败2');

            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            return [false, 0];
        }

        LastMsgCache::set(['created_at' => date('Y-m-d H:i:s'), 'text' => '入群通知'], $group_id, 0);
        return [true, $result->id];
    }

    /**
     * 退出群组
     *
     * @param int $user_id 用户ID
     * @param int $group_id 群组ID
     * @return array
     */
    public function quit(int $user_id, int $group_id)
    {
        $record_id = 0;
        Db::beginTransaction();
        try {
            $res = UsersGroupMember::where('group_id', $group_id)->where('user_id', $user_id)->where('group_owner', 0)->update(['status' => 1]);
            if ($res) {
                UsersChatList::where('uid', $user_id)->where('type', 2)->where('group_id', $group_id)->update(['status' => 0]);

                $result = ChatRecord::create([
                    'msg_type' => 3,
                    'source' => 2,
                    'user_id' => 0,
                    'receive_id' => $group_id,
                    'content' => $user_id,
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                if (!$result) {
                    throw new Exception('添加群通知记录失败 : quitGroupChat');
                }

                $result2 = ChatRecordsInvite::create([
                    'record_id' => $result->id,
                    'type' => 2,
                    'operate_user_id' => $user_id,
                    'user_ids' => $user_id
                ]);

                if (!$result2) {
                    throw new Exception('添加群通知记录失败2  : quitGroupChat');
                }

                UsersChatList::where('uid', $user_id)->where('group_id', $group_id)->update(['status' => 0, 'updated_at' => date('Y-m-d H:i:s')]);

                $record_id = $result->id;
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return [false, 0];
        }

        return [true, $record_id];
    }

    /**
     * 踢出群组(管理员特殊权限)
     *
     * @param int $group_id 群ID
     * @param int $user_id 操作用户ID
     * @param array $member_ids 群成员ID
     * @return array
     */
    public function removeMember(int $group_id, int $user_id, array $member_ids)
    {
        if (!UsersGroup::isManager($user_id, $group_id)) {
            return [false, 0];
        }

        Db::beginTransaction();
        try {
            //更新用户状态
            if (!UsersGroupMember::where('group_id', $group_id)->whereIn('user_id', $member_ids)->where('group_owner', 0)->update(['status' => 1])) {
                throw new Exception('修改群成员状态失败');
            }

            $result = ChatRecord::create([
                'msg_type' => 3,
                'source' => 2,
                'user_id' => 0,
                'receive_id' => $group_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if (!$result) {
                throw new Exception('添加群通知记录失败1');
            }

            $result2 = ChatRecordsInvite::create([
                'record_id' => $result->id,
                'type' => 3,
                'operate_user_id' => $user_id,
                'user_ids' => implode(',', $member_ids)
            ]);

            if (!$result2) {
                throw new Exception('添加群通知记录失败2');
            }

            foreach ($member_ids as $member_id) {
                UsersChatList::where('uid', $member_id)->where('group_id', $group_id)->update(['status' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return [false, 0];
        }

        return [true, $result->id];
    }
}
