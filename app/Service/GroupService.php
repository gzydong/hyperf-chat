<?php
declare(strict_types=1);

namespace App\Service;

use App\Cache\LastMessage;
use App\Cache\SocketRoom;
use App\Constants\TalkMessageEvent;
use App\Constants\TalkMessageType;
use App\Constants\TalkMode;
use App\Model\Talk\TalkRecords;
use App\Model\Talk\TalkRecordsInvite;
use App\Model\Group\Group;
use App\Model\Group\GroupMember;
use App\Model\Talk\TalkList;
use App\Support\MessageProducer;
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
     * 创建群组
     *
     * @param int   $user_id    用户ID
     * @param array $group_info 群聊名称
     * @param array $friend_ids 好友的用户ID
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
            $group = Group::create([
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

            foreach ($friend_ids as $friend_id) {
                $groupMember[] = [
                    'group_id'   => $group->id,
                    'user_id'    => $friend_id,
                    'leader'     => $user_id == $friend_id ? 2 : 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $chatList[] = [
                    'talk_type'   => TalkMode::GROUP_CHAT,
                    'user_id'     => $friend_id,
                    'receiver_id' => $group->id,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s')
                ];
            }

            if (!Db::table('group_member')->insert($groupMember)) {
                throw new Exception('创建群成员信息失败');
            }

            if (!Db::table('talk_list')->insert($chatList)) {
                throw new Exception('创建群成员的聊天列表失败');
            }

            $result = TalkRecords::create([
                'talk_type'   => TalkMode::GROUP_CHAT,
                'user_id'     => 0,
                'receiver_id' => $group->id,
                'msg_type'    => TalkMessageType::GROUP_INVITE_MESSAGE,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s')
            ]);

            TalkRecordsInvite::create([
                'record_id'       => $result->id,
                'type'            => 1,
                'operate_user_id' => $user_id,
                'user_ids'        => $invite_ids
            ]);

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return [false, null];
        }

        LastMessage::getInstance()->save(TalkMode::GROUP_CHAT, $user_id, $group->id, [
            'text'       => '[入群通知]',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // 加入聊天室
        foreach ($friend_ids as $value) {
            SocketRoom::getInstance()->addRoomMember(strval($group->id), strval($value));
        }

        MessageProducer::publish(MessageProducer::create(TalkMessageEvent::EVENT_TALK, [
            'sender_id'   => $user_id,
            'receiver_id' => $group->id,
            'talk_type'   => TalkMode::GROUP_CHAT,
            'record_id'   => $result->id
        ]));

        return [true, $group];
    }

    /**
     * @param int   $group_id
     * @param int   $user_id
     * @param array $params
     * @return bool
     */
    public function update(int $group_id, int $user_id, array $params)
    {
        return (bool)Group::where('id', $group_id)->where('creator_id', $user_id)->update([
            'group_name' => $params['group_name'] ?? '',
            'profile'    => $params['profile'] ?? '',
            'avatar'     => $params['avatar'] ?? ''
        ]);
    }

    /**
     * 解散群组(群主权限)
     *
     * @param int $group_id 群ID
     * @param int $user_id  用户ID
     * @return bool
     */
    public function dismiss(int $group_id, int $user_id)
    {
        $group = Group::where('id', $group_id)->first(['creator_id', 'is_dismiss']);
        if (!$group || $group->creator_id != $user_id || $group->is_dismiss == 1) {
            return false;
        }

        try {
            DB::transaction(function () use ($group_id, $user_id) {
                Group::where('id', $group_id)->where('creator_id', $user_id)->update([
                    'is_dismiss'   => 1,
                    'dismissed_at' => date('Y-m-d H:i:s'),
                ]);

                GroupMember::where('group_id', $group_id)->update([
                    'is_quit'    => 1,
                    'deleted_at' => date('Y-m-d H:i:s'),
                ]);
            });
        } catch (\Exception $e) {
            return false;
        }

        SocketRoom::getInstance()->delRoom($group_id);

        // ... TODO 推送群消息(预留)

        return true;
    }

    /**
     * 邀请加入群组
     *
     * @param int   $user_id    用户ID
     * @param int   $group_id   聊天群ID
     * @param array $friend_ids 被邀请的用户ID
     * @return bool
     */
    public function invite(int $user_id, int $group_id, $friend_ids = [])
    {
        if (!$friend_ids) return false;

        $info = GroupMember::where('group_id', $group_id)->where('user_id', $user_id)->first(['id', 'is_quit']);

        // 判断主动邀请方是否属于聊天群成员
        if (!$info && $info->is_quit == 1) {
            return false;
        }

        $updateArr = $insertArr = $updateArr1 = $insertArr1 = [];

        $members = GroupMember::where('group_id', $group_id)->whereIn('user_id', $friend_ids)->get(['id', 'user_id', 'is_quit'])->keyBy('user_id')->toArray();
        $chatArr = TalkList::where('talk_type', TalkMode::GROUP_CHAT)
            ->where('receiver_id', $group_id)
            ->whereIn('user_id', $friend_ids)
            ->get(['id', 'user_id', 'is_delete'])
            ->keyBy('user_id')->toArray();

        foreach ($friend_ids as $uid) {
            if (!isset($members[$uid])) {
                $insertArr[] = [
                    'group_id'   => $group_id,
                    'user_id'    => $uid,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            } else if ($members[$uid]['is_quit'] == 1) {
                $updateArr[] = $members[$uid]['id'];
            }

            if (!isset($chatArr[$uid])) {
                $insertArr1[] = [
                    'talk_type'   => TalkMode::GROUP_CHAT,
                    'user_id'     => $uid,
                    'receiver_id' => $group_id,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s')
                ];
            } else if ($chatArr[$uid]['is_delete'] == 1) {
                $updateArr1[] = $chatArr[$uid]['id'];
            }
        }

        Db::beginTransaction();
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
                TalkList::whereIn('id', $updateArr1)->update([
                    'is_delete'  => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            if ($insertArr1) {
                Db::table('talk_list')->insert($insertArr1);
            }

            $result = TalkRecords::create([
                'talk_type'   => TalkMode::GROUP_CHAT,
                'user_id'     => 0,
                'receiver_id' => $group_id,
                'msg_type'    => TalkMessageType::GROUP_INVITE_MESSAGE,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s')
            ]);

            TalkRecordsInvite::create([
                'record_id'       => $result->id,
                'type'            => 1,
                'operate_user_id' => $user_id,
                'user_ids'        => implode(',', $friend_ids)
            ]);

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return false;
        }

        LastMessage::getInstance()->save(TalkMode::GROUP_CHAT, $user_id, $group_id, [
            'text'       => '[入群通知]',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // 加入聊天室
        foreach ($friend_ids as $value) {
            SocketRoom::getInstance()->addRoomMember(strval($group_id), strval($value));
        }

        MessageProducer::publish(MessageProducer::create(TalkMessageEvent::EVENT_TALK, [
            'sender_id'   => $user_id,
            'receiver_id' => $group_id,
            'talk_type'   => TalkMode::GROUP_CHAT,
            'record_id'   => $result->id
        ]));

        return true;
    }

    /**
     * 退出群组(仅普通管理员及群成员)
     *
     * @param int $user_id  用户ID
     * @param int $group_id 群组ID
     * @return bool
     */
    public function quit(int $user_id, int $group_id)
    {
        // 判断是否属于管理员
        if (Group::isManager($user_id, $group_id)) {
            return false;
        }

        Db::beginTransaction();
        try {
            $count = GroupMember::where('group_id', $group_id)->where('user_id', $user_id)->where('is_quit', 0)->update([
                'is_quit'    => 1,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);

            if ($count == 0) throw new Exception('更新记录失败...');

            $result = TalkRecords::create([
                'talk_type'   => TalkMode::GROUP_CHAT,
                'user_id'     => 0,
                'receiver_id' => $group_id,
                'msg_type'    => TalkMessageType::GROUP_INVITE_MESSAGE,
                'content'     => $user_id,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s')
            ]);

            TalkRecordsInvite::create([
                'record_id'       => $result->id,
                'type'            => 2,
                'operate_user_id' => $user_id,
                'user_ids'        => $user_id
            ]);

            TalkList::where([
                ['talk_type', '=', TalkMode::GROUP_CHAT],
                ['user_id', '=', $user_id],
                ['receiver_id', '=', $group_id],
            ])->update(['is_delete' => 1]);

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return false;
        }

        // 移出聊天室
        SocketRoom::getInstance()->delRoomMember(strval($group_id), strval($user_id));

        MessageProducer::publish(MessageProducer::create(TalkMessageEvent::EVENT_TALK, [
            'sender_id'   => $user_id,
            'receiver_id' => (int)$params['group_id'],
            'talk_type'   => TalkMode::GROUP_CHAT,
            'record_id'   => $result->id
        ]));

        return true;
    }

    /**
     * 踢出群组(管理员特殊权限)
     *
     * @param int   $group_id   群ID
     * @param int   $user_id    操作用户ID
     * @param array $member_ids 群成员ID
     * @return bool
     */
    public function removeMember(int $group_id, int $user_id, array $member_ids)
    {
        if (!Group::isManager($user_id, $group_id)) {
            return false;
        }

        Db::beginTransaction();
        try {
            $count = GroupMember::where('group_id', $group_id)->whereIn('user_id', $member_ids)->where('is_quit', 0)->update([
                'is_quit'    => 1,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);

            if ($count == 0) throw new Exception('更新记录失败...');

            $result = TalkRecords::create([
                'talk_type'   => TalkMode::GROUP_CHAT,
                'user_id'     => 0,
                'receiver_id' => $group_id,
                'msg_type'    => TalkMessageType::GROUP_INVITE_MESSAGE,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

            TalkRecordsInvite::create([
                'record_id'       => $result->id,
                'type'            => 3,
                'operate_user_id' => $user_id,
                'user_ids'        => implode(',', $member_ids)
            ]);

            TalkList::whereIn('user_id', $member_ids)->where('receiver_id', $group_id)->where('talk_type', TalkMode::GROUP_CHAT)->update([
                'is_delete'  => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return false;
        }

        // 移出聊天室
        foreach ($member_ids as $uid) {
            SocketRoom::getInstance()->delRoomMember(strval($group_id), strval($uid));
        }

        MessageProducer::publish(MessageProducer::create(TalkMessageEvent::EVENT_TALK, [
            'sender_id'   => $user_id,
            'receiver_id' => $group_id,
            'talk_type'   => TalkMode::GROUP_CHAT,
            'record_id'   => $result->id
        ]));

        return true;
    }

    /**
     * 更新用户群名片
     *
     * @param int    $group_id  群ID
     * @param int    $user_id   用户ID
     * @param string $user_card 用户名片
     * @return bool
     */
    public function updateMemberCard(int $group_id, int $user_id, string $user_card)
    {
        return (bool)GroupMember::where('group_id', $group_id)->where('user_id', $user_id)->update(['user_card' => $user_card]);
    }

    /**
     * 获取用户所在的群聊
     *
     * @param int $user_id 用户ID
     * @return array
     */
    public function getUserGroups(int $user_id): array
    {
        $fields = [
            'group.id',
            'group.group_name',
            'group.avatar',
            'group.profile',
            'group_member.leader',
        ];

        $items = GroupMember::join('group', 'group.id', '=', 'group_member.group_id')
            ->where([
                ['group_member.user_id', '=', $user_id],
                ['group_member.is_quit', '=', 0]
            ])->orderBy('id', 'desc')->get($fields)->toArray();

        $list = [];
        if ($items) {
            $list = TalkList::query()->where('user_id', $user_id)
                ->where('talk_type', TalkMode::GROUP_CHAT)
                ->whereIn('receiver_id', array_column($items, 'id'))
                ->get(['receiver_id', 'is_disturb'])->keyBy('receiver_id')->toArray();
        }

        foreach ($items as $key => $item) {
            $items[$key]['is_disturb'] = isset($list[$item['id']]) ? $list[$item['id']]['is_disturb'] : 0;
        }

        return $items;
    }
}
