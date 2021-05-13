<?php
/**
 * This is my open source code, please do not use it for commercial applications.
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author Yuandong<837215079@qq.com>
 * @link   https://github.com/gzydong/hyperf-chat
 */

namespace App\Controller\Api\V1;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use App\Model\UsersFriend;
use App\Model\UsersChatList;
use App\Model\Group\Group;
use App\Model\Group\GroupMember;
use App\Model\Group\GroupNotice;
use App\Amqp\Producer\ChatMessageProducer;
use App\Service\SocketRoomService;
use App\Service\GroupService;
use App\Constants\SocketConstants;
use Psr\Http\Message\ResponseInterface;

/**
 * Class GroupController
 * @Controller(path="/api/v1/group")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class GroupController extends CController
{
    /**
     * @Inject
     * @var GroupService
     */
    private $groupService;

    /**
     * @Inject
     * @var SocketRoomService
     */
    private $socketRoomService;

    /**
     * 创建群组
     * @RequestMapping(path="create", methods="post")
     *
     * @return ResponseInterface
     */
    public function create()
    {
        $params = $this->request->inputs(['group_name', 'uids']);
        $this->validate($params, [
            'group_name' => 'required',
            'uids'       => 'required|ids'
        ]);

        $friend_ids = parse_ids($params['uids']);

        $user_id = $this->uid();
        [$isTrue, $data] = $this->groupService->create($user_id, [
            'name'    => $params['group_name'],
            'avatar'  => $params['avatar'] ?? '',
            'profile' => $params['group_profile'] ?? ''
        ], $friend_ids);

        if (!$isTrue) {
            return $this->response->fail('创建群聊失败，请稍后再试！');
        }

        // 加入聊天室
        $friend_ids[] = $user_id;
        foreach ($friend_ids as $uid) {
            $this->socketRoomService->addRoomMember($uid, $data['group_id']);
        }

        // ... 消息推送队列
        push_amqp(new ChatMessageProducer(SocketConstants::EVENT_TALK, [
            'sender'    => $user_id,                   // 发送者ID
            'receive'   => (int)$data['group_id'],     // 接收者ID
            'source'    => 2,                          // 接收者类型[1:好友;2:群组;]
            'record_id' => (int)$data['record_id']
        ]));

        return $this->response->success([
            'group_id' => $data['group_id']
        ]);
    }

    /**
     * 解散群组接口
     * @RequestMapping(path="dismiss", methods="post")
     *
     * @return ResponseInterface
     */
    public function dismiss()
    {
        $params = $this->request->inputs(['group_id']);
        $this->validate($params, [
            'group_id' => 'required|integer'
        ]);

        $isTrue = $this->groupService->dismiss($params['group_id'], $this->uid());
        if (!$isTrue) {
            return $this->response->fail('群组解散失败！');
        }

        $this->socketRoomService->delRoom($params['group_id']);

        // ... TODO 推送群消息(预留)

        return $this->response->success([], '群组解散成功...');
    }

    /**
     * 邀请好友加入群组接口
     * @RequestMapping(path="invite", methods="post")
     *
     * @return ResponseInterface
     */
    public function invite()
    {
        $params = $this->request->inputs(['group_id', 'uids']);
        $this->validate($params, [
            'group_id' => 'required|integer',
            'uids'     => 'required|ids'
        ]);

        $uids = parse_ids($params['uids']);

        $user_id = $this->uid();
        [$isTrue, $record_id] = $this->groupService->invite($user_id, $params['group_id'], $uids);
        if (!$isTrue) {
            return $this->response->fail('邀请好友加入群聊失败！');
        }

        // 加入聊天室
        foreach ($uids as $uid) {
            $this->socketRoomService->addRoomMember($uid, $params['group_id']);
        }

        // 消息推送队列
        push_amqp(new ChatMessageProducer(SocketConstants::EVENT_TALK, [
            'sender'    => $user_id,                     // 发送者ID
            'receive'   => (int)$params['group_id'],     // 接收者ID
            'source'    => 2,                            // 接收者类型[1:好友;2:群组;]
            'record_id' => $record_id
        ]));

        return $this->response->success([], '好友已成功加入群聊...');
    }

    /**
     * 退出群组接口
     * @RequestMapping(path="secede", methods="post")
     *
     * @return ResponseInterface
     */
    public function secede()
    {
        $params = $this->request->inputs(['group_id']);
        $this->validate($params, [
            'group_id' => 'required|integer'
        ]);

        $user_id = $this->uid();
        [$isTrue, $record_id] = $this->groupService->quit($user_id, $params['group_id']);
        if (!$isTrue) {
            return $this->response->fail('退出群组失败！');
        }

        // 移出聊天室
        $this->socketRoomService->delRoomMember($params['group_id'], $user_id);

        // 消息推送队列
        push_amqp(new ChatMessageProducer(SocketConstants::EVENT_TALK, [
            'sender'    => $user_id,                     // 发送者ID
            'receive'   => (int)$params['group_id'],     // 接收者ID
            'source'    => 2,                            // 接收者类型[1:好友;2:群组;]
            'record_id' => $record_id
        ]));

        return $this->response->success([], '已成功退出群组...');
    }

    /**
     * 编辑群组信息
     * @RequestMapping(path="edit", methods="post")
     *
     * @return ResponseInterface
     */
    public function editDetail()
    {
        $params = $this->request->inputs(['group_id', 'group_name', 'profile', 'avatar']);
        $this->validate($params, [
            'group_id'   => 'required|integer',
            'group_name' => 'required|max:30',
            'profile'    => 'present|max:100',
            'avatar'     => 'present|url'
        ]);

        $result = Group::where('id', $params['group_id'])->where('creator_id', $this->uid())->update([
            'group_name' => $params['group_name'],
            'profile'    => $params['profile'],
            'avatar'     => $params['avatar']
        ]);

        // ... TODO 推送消息通知（预留）

        return $result
            ? $this->response->success([], '群组信息修改成功...')
            : $this->response->fail('群组信息修改失败！');
    }

    /**
     * 移除指定成员（管理员权限）
     * @RequestMapping(path="remove-members", methods="post")
     *
     * @return ResponseInterface
     */
    public function removeMembers()
    {
        $params = $this->request->inputs(['group_id', 'members_ids']);
        $this->validate($params, [
            'group_id'    => 'required|integer',
            'members_ids' => 'required|ids'
        ]);

        $params['members_ids'] = parse_ids($params['members_ids']);

        $user_id = $this->uid();
        if (in_array($user_id, $params['members_ids'])) {
            return $this->response->fail('群聊用户移除失败！');
        }

        [$isTrue, $record_id] = $this->groupService->removeMember($params['group_id'], $user_id, $params['members_ids']);
        if (!$isTrue) {
            return $this->response->fail('群聊用户移除失败！');
        }

        // 移出聊天室
        foreach ($params['members_ids'] as $uid) {
            $this->socketRoomService->delRoomMember($params['group_id'], $uid);
        }

        // 消息推送队列
        push_amqp(new ChatMessageProducer(SocketConstants::EVENT_TALK, [
            'sender'    => $user_id,                     // 发送者ID
            'receive'   => (int)$params['group_id'],     // 接收者ID
            'source'    => 2,                            // 接收者类型[1:好友;2:群组;]
            'record_id' => $record_id
        ]));

        return $this->response->success([], '已成功退出群组...');
    }

    /**
     * 获取群信息接口
     * @RequestMapping(path="detail", methods="get")
     *
     * @return ResponseInterface
     */
    public function detail()
    {
        $group_id = $this->request->input('group_id', 0);
        $user_id  = $this->uid();

        $groupInfo = Group::leftJoin('users', 'users.id', '=', 'group.creator_id')
            ->where('group.id', $group_id)->where('group.is_dismiss', 0)->first([
                'group.id',
                'group.creator_id',
                'group.group_name',
                'group.profile',
                'group.avatar',
                'group.created_at',
                'users.nickname'
            ]);

        if (!$groupInfo) {
            return $this->response->success();
        }

        $notice = GroupNotice::where('group_id', $group_id)
            ->where('is_delete', 0)
            ->orderBy('id', 'desc')
            ->first(['title', 'content']);

        return $this->response->success([
            'group_id'         => $groupInfo->id,
            'group_name'       => $groupInfo->group_name,
            'group_profile'    => $groupInfo->profile,
            'avatar'           => $groupInfo->avatar,
            'created_at'       => $groupInfo->created_at,
            'is_manager'       => $groupInfo->creator_id == $user_id,
            'manager_nickname' => $groupInfo->nickname,
            'visit_card'       => GroupMember::visitCard($user_id, $group_id),
            'not_disturb'      => UsersChatList::where('uid', $user_id)->where('group_id', $group_id)->where('type', 2)->value('not_disturb') ?? 0,
            'notice'           => $notice ? $notice->toArray() : []
        ]);
    }

    /**
     * 设置群名片
     * @RequestMapping(path="set-group-card", methods="post")
     *
     * @return ResponseInterface
     */
    public function editGroupCard()
    {
        $params = $this->request->inputs(['group_id', 'visit_card']);
        $this->validate($params, [
            'group_id'   => 'required|integer',
            'visit_card' => 'required|max:20'
        ]);

        $isTrue = GroupMember::where('group_id', $params['group_id'])
            ->where('user_id', $this->uid())
            ->update(['user_card' => $params['visit_card']]);

        return $isTrue
            ? $this->response->success([], '群名片修改成功...')
            : $this->response->error('群名片修改失败！');
    }

    /**
     * 获取可邀请加入群组的好友列表
     * @RequestMapping(path="invite-friends", methods="get")
     *
     * @return ResponseInterface
     */
    public function getInviteFriends()
    {
        $group_id = $this->request->input('group_id', 0);
        $friends  = UsersFriend::getUserFriends($this->uid());
        if ($group_id > 0 && $friends) {
            if ($ids = GroupMember::getGroupMemberIds($group_id)) {
                foreach ($friends as $k => $item) {
                    if (in_array($item['id'], $ids)) unset($friends[$k]);
                }
            }

            $friends = array_values($friends);
        }

        return $this->response->success($friends);
    }

    /**
     * 获取群组列表
     * @RequestMapping(path="list", methods="get")
     *
     * @return ResponseInterface
     */
    public function getGroups()
    {
        return $this->response->success(
            $this->groupService->getGroups($this->uid())
        );
    }

    /**
     * 获取群组成员列表
     * @RequestMapping(path="members", methods="get")
     *
     * @return ResponseInterface
     */
    public function getGroupMembers()
    {
        $user_id  = $this->uid();
        $group_id = $this->request->input('group_id', 0);

        // 判断用户是否是群成员
        if (!Group::isMember($group_id, $user_id)) {
            return $this->response->fail('非法操作！');
        }

        $members = GroupMember::select([
            'group_member.id',
            'group_member.leader',
            'group_member.user_card',
            'group_member.user_id',
            'users.avatar',
            'users.nickname',
            'users.gender',
            'users.motto',
        ])
            ->leftJoin('users', 'users.id', '=', 'group_member.user_id')
            ->where([
                ['group_member.group_id', '=', $group_id],
                ['group_member.is_quit', '=', 0],
            ])->orderBy('leader', 'desc')->get()->toArray();

        return $this->response->success($members);
    }

    /**
     * 获取群组公告列表
     * @RequestMapping(path="notices", methods="get")
     *
     * @return ResponseInterface
     */
    public function getGroupNotice()
    {
        $user_id  = $this->uid();
        $group_id = $this->request->input('group_id', 0);

        // 判断用户是否是群成员
        if (!Group::isMember($group_id, $user_id)) {
            return $this->response->fail('非管理员禁止操作！');
        }

        $rows = GroupNotice::leftJoin('users', 'users.id', '=', 'group_notice.creator_id')
            ->where([
                ['group_notice.group_id', '=', $group_id],
                ['group_notice.is_delete', '=', 0]
            ])
            ->orderBy('group_notice.is_top', 'desc')
            ->orderBy('group_notice.updated_at', 'desc')
            ->get([
                'group_notice.id',
                'group_notice.creator_id',
                'group_notice.title',
                'group_notice.content',
                'group_notice.is_top',
                'group_notice.is_confirm',
                'group_notice.confirm_users',
                'group_notice.created_at',
                'group_notice.updated_at',
                'users.avatar',
                'users.nickname',
            ])->toArray();

        return $this->response->success($rows);
    }

    /**
     * 创建/编辑群公告
     * @RequestMapping(path="edit-notice", methods="post")
     *
     * @return ResponseInterface
     */
    public function editNotice()
    {
        $params = $this->request->inputs(['group_id', 'notice_id', 'title', 'content', 'is_top', 'is_confirm']);
        $this->validate($params, [
            'notice_id'  => 'required|integer',
            'group_id'   => 'required|integer',
            'title'      => 'required|max:50',
            'is_top'     => 'integer|in:0,1',
            'is_confirm' => 'integer|in:0,1',
            'content'    => 'required'
        ]);

        $user_id = $this->uid();

        // 判断用户是否是管理员
        if (!Group::isManager($user_id, $params['group_id'])) {
            return $this->response->fail('非管理员禁止操作！');
        }

        // 判断是否是新增数据
        if (empty($params['notice_id'])) {
            $result = GroupNotice::create([
                'group_id'   => $params['group_id'],
                'creator_id' => $user_id,
                'title'      => $params['title'],
                'content'    => $params['content'],
                'is_top'     => $params['is_top'],
                'is_confirm' => $params['is_confirm'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!$result) {
                return $this->response->fail('添加群公告信息失败！');
            }

            // ... TODO 推送群消息（预留）

            return $this->response->success([], '添加群公告信息成功...');
        }

        $result = GroupNotice::where('id', $params['notice_id'])->update([
            'title'      => $params['title'],
            'content'    => $params['content'],
            'is_top'     => $params['is_top'],
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $result
            ? $this->response->success([], '修改群公告信息成功...')
            : $this->response->fail('修改群公告信息失败！');
    }

    /**
     * 删除群公告(软删除)
     * @RequestMapping(path="delete-notice", methods="post")
     *
     * @return ResponseInterface
     */
    public function deleteNotice()
    {
        $params = $this->request->inputs(['group_id', 'notice_id']);
        $this->validate($params, [
            'group_id'  => 'required|integer',
            'notice_id' => 'required|integer'
        ]);

        $user_id = $this->uid();

        // 判断用户是否是管理员
        if (!Group::isManager($user_id, $params['group_id'])) {
            return $this->response->fail('非法操作！');
        }

        $result = GroupNotice::where('id', $params['notice_id'])
            ->where('group_id', $params['group_id'])
            ->update([
                'is_delete'  => 1,
                'deleted_at' => date('Y-m-d H:i:s')
            ]);

        return $result
            ? $this->response->success([], '公告删除成功...')
            : $this->response->fail('公告删除失败！');
    }
}
