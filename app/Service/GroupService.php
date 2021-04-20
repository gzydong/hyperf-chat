<?php
declare(strict_types=1);

namespace App\Service;

use App\Cache\LastMsgCache;
use App\Model\Chat\ChatRecord;
use App\Model\Chat\ChatRecordsInvite;
use App\Model\Group\Group;
use App\Model\Group\GroupMember;
use App\Model\UsersChatList;
use Hyperf\DbConnection\Db;
use Exception;

/**
 * Class GroupService
 *
 * @package App\Service
 */
class GroupService extends BaseService
{
    /**
     * 获取用户所在的群聊
     *
     * @param int $user_id 用户ID
     *
     * @return array
     */
    public function getGroups(int $user_id): array
    {
        $items = GroupMember::join('group', 'group.id', '=', 'group_member.group_id')
            ->where([
                ['group_member.user_id', '=', $user_id],
                ['group_member.is_quit', '=', 0]
            ])
            ->orderBy('id', 'desc')
            ->get([
                'group.id',
                'group.group_name',
                'group.avatar',
                'group.profile',
                'group_member.leader',
            ])->toArray();

        $arr = UsersChatList::where([
            ['uid', '=', $user_id],
            ['type', '=', 2],
        ])->get(['group_id', 'not_disturb'])->keyBy('group_id')->toArray();

        foreach ($items as $key => $item) {
            $items[$key]['not_disturb'] = isset($arr[$item['id']]) ? $arr[$item['id']]['not_disturb'] : 0;
        }

        return $items;
    }

    /**
     * 创建群组
     *
     * @param int   $user_id    用户ID
     * @param array $group_info 群聊名称
     * @param array $friend_ids 好友的用户ID
     *
     * @return array
     */
    public function create(int $user_id, array $group_info, $friend_ids = [])
    {
        $invite_ids   = implode(',', $friend_ids);
        $friend_ids[] = $user_id;
        $groupMember  = [];
        $chatList     = [];

        Db::beginTransaction();
        try {
            $insRes = Group::create([
                'creator_id' => $user_id,
                'group_name' => $group_info['name'],
                'avatar'     => $group_info['avatar'],
                'profile'    => $group_info['profile'],
                'max_num'    => Group::MAX_MEMBER_NUM,
                'is_overt'   => 0,
                'is_mute'    => 0,
                'is_dismiss' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            foreach ($friend_ids as $k => $uid) {
                $groupMember[] = [
                    'group_id'   => $insRes->id,
                    'user_id'    => $uid,
                    'leader'     => $user_id == $uid ? 2 : 0,
                    'is_mute'    => 0,
                    'is_quit'    => 0,
                    'user_card'  => '',
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $chatList[] = [
                    'type'       => 2,
                    'uid'        => $uid,
                    'friend_id'  => 0,
                    'group_id'   => $insRes->id,
                    'status'     => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }

            if (!Db::table('group_member')->insert($groupMember)) {
                throw new Exception('创建群成员信息失败');
            }

            if (!Db::table('users_chat_list')->insert($chatList)) {
                throw new Exception('创建群成员的聊天列表失败');
            }

            $result = ChatRecord::create([
                'msg_type'   => 3,
                'source'     => 2,
                'user_id'    => 0,
                'receive_id' => $insRes->id,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            ChatRecordsInvite::create([
                'record_id'       => $result->id,
                'type'            => 1,
                'operate_user_id' => $user_id,
                'user_ids'        => $invite_ids
            ]);

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return [false, 0];
        }

        // 设置群聊消息缓存
        LastMsgCache::set(['created_at' => date('Y-m-d H:i:s'), 'text' => '入群通知'], $insRes->id, 0);

        return [true, ['record_id' => $result->id, 'group_id' => $insRes->id]];
    }

    /**
     * 解散群组(群主权限)
     *
     * @param int $group_id 群ID
     * @param int $user_id  用户ID
     *
     * @return bool
     */
    public function dismiss(int $group_id, int $user_id)
    {
        $group = Group::where('id', $group_id)->first(['creator_id', 'is_dismiss']);
        if (!$group || $group->creator_id != $user_id || $group->is_dismiss == 1) {
            return false;
        }

        DB::transaction(function () use ($group_id, $user_id) {
            Group::where('id', $group_id)->where('creator_id', $user_id)->update([
                'is_dismiss'   => 1,
                'dismissed_at' => date('Y-m-d H:i:s'),
            ]);

            GroupMember::where('group_id', $group_id)->update([
                'is_quit'    => 1,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);
        }, 2);

        return true;
    }

    /**
     * 邀请加入群组
     *
     * @param int   $user_id    用户ID
     * @param int   $group_id   聊天群ID
     * @param array $friend_ids 被邀请的用户ID
     *
     * @return array
     */
    public function invite(int $user_id, int $group_id, $friend_ids = [])
    {
        if (!$friend_ids) return [false, 0];

        $info = GroupMember::where('group_id', $group_id)->where('user_id', $user_id)->first(['id', 'is_quit']);

        // 判断主动邀请方是否属于聊天群成员
        if (!$info && $info->is_quit == 1) {
            return [false, 0];
        }

        $updateArr = $insertArr = $updateArr1 = $insertArr1 = [];

        $members = GroupMember::where('group_id', $group_id)->whereIn('user_id', $friend_ids)->get(['id', 'user_id', 'is_quit'])->keyBy('user_id')->toArray();
        $chatArr = UsersChatList::where('group_id', $group_id)->whereIn('uid', $friend_ids)->get(['id', 'uid', 'status'])->keyBy('uid')->toArray();

        foreach ($friend_ids as $uid) {
            if (!isset($members[$uid])) {
                $insertArr[] = [
                    'group_id'   => $group_id,
                    'user_id'    => $uid,
                    'leader'     => 0,
                    'is_mute'    => 0,
                    'is_quit'    => 0,
                    'user_card'  => '',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            } else if ($members[$uid]['status'] == 1) {
                $updateArr[] = $members[$uid]['id'];
            }

            if (!isset($chatArr[$uid])) {
                $insertArr1[] = [
                    'type'       => 2,
                    'uid'        => $uid,
                    'friend_id'  => 0,
                    'group_id'   => $group_id,
                    'status'     => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            } else if ($chatArr[$uid]['status'] == 0) {
                $updateArr1[] = $chatArr[$uid]['id'];
            }
        }

        try {
            if ($updateArr) {
                GroupMember::whereIn('id', $updateArr)->update([
                    'leader'     => 0,
                    'is_mute'    => 0,
                    'is_quit'    => 0,
                    'user_card'  => '',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            if ($insertArr) {
                Db::table('group_member')->insert($insertArr);
            }

            if ($updateArr1) {
                UsersChatList::whereIn('id', $updateArr1)->update([
                    'status'     => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            if ($insertArr1) {
                Db::table('users_chat_list')->insert($insertArr1);
            }

            $result = ChatRecord::create([
                'msg_type'   => 3,
                'source'     => 2,
                'user_id'    => 0,
                'receive_id' => $group_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            ChatRecordsInvite::create([
                'record_id'       => $result->id,
                'type'            => 1,
                'operate_user_id' => $user_id,
                'user_ids'        => implode(',', $friend_ids)
            ]);

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return [false, 0];
        }

        LastMsgCache::set(['created_at' => date('Y-m-d H:i:s'), 'text' => '入群通知'], $group_id, 0);
        return [true, $result->id];
    }

    /**
     * 退出群组(仅普通管理员及群成员)
     *
     * @param int $user_id  用户ID
     * @param int $group_id 群组ID
     *
     * @return array
     */
    public function quit(int $user_id, int $group_id)
    {
        if (Group::isManager($user_id, $group_id)) {
            return [false, 0];
        }

        Db::beginTransaction();
        try {
            $count = GroupMember::where('group_id', $group_id)->where('user_id', $user_id)->where('is_quit', 0)->update([
                'is_quit'    => 1,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);

            if ($count == 0) {
                throw new Exception('更新记录失败...');
            }

            $result = ChatRecord::create([
                'msg_type'   => 3,
                'source'     => 2,
                'user_id'    => 0,
                'receive_id' => $group_id,
                'content'    => $user_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $record_id = $result->id;

            ChatRecordsInvite::create([
                'record_id'       => $result->id,
                'type'            => 2,
                'operate_user_id' => $user_id,
                'user_ids'        => $user_id
            ]);

            UsersChatList::where('uid', $user_id)->where('type', 2)->where('group_id', $group_id)->update(['status' => 0]);

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
     * @param int   $group_id   群ID
     * @param int   $user_id    操作用户ID
     * @param array $member_ids 群成员ID
     *
     * @return array
     */
    public function removeMember(int $group_id, int $user_id, array $member_ids)
    {
        if (!Group::isManager($user_id, $group_id)) {
            return [false, 0];
        }

        Db::beginTransaction();
        try {
            $count = GroupMember::where('group_id', $group_id)->whereIn('user_id', $member_ids)->where('is_quit', 0)->update([
                'is_quit'    => 1,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);

            if ($count == 0) {
                throw new Exception('更新记录失败...');
            }

            $result = ChatRecord::create([
                'msg_type'   => 3,
                'source'     => 2,
                'user_id'    => 0,
                'receive_id' => $group_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            ChatRecordsInvite::create([
                'record_id'       => $result->id,
                'type'            => 3,
                'operate_user_id' => $user_id,
                'user_ids'        => implode(',', $member_ids)
            ]);

            UsersChatList::whereIn('uid', $member_ids)->where('group_id', $group_id)->update([
                'status'     => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return [false, 0];
        }

        return [true, $result->id];
    }
}
