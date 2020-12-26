<?php
/**
 *
 * This is my open source code, please do not use it for commercial applications.
 *
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
use Hyperf\Amqp\Producer;
use App\Model\UsersFriend;
use App\Model\UsersChatList;
use App\Model\Group\UsersGroup;
use App\Model\Group\UsersGroupMember;
use App\Model\Group\UsersGroupNotice;
use App\Amqp\Producer\ChatMessageProducer;
use App\Service\SocketRoomService;
use App\Service\GroupService;
use App\Constants\SocketConstants;

/**
 * Class GroupController
 *
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
     * @var Producer
     */
    private $producer;

    /**
     * @Inject
     * @var SocketRoomService
     */
    private $socketRoomService;

    /**
     * 创建群组
     *
     * @RequestMapping(path="create", methods="post")
     *
     * @return mixed
     */
    public function create()
    {
        $params = $this->request->inputs(['group_name', 'uids']);
        $this->validate($params, [
            'group_name' => 'required',
            'uids' => 'required|ids'
        ]);

        $friend_ids = parse_ids($params['uids']);

        $user_id = $this->uid();
        [$isTrue, $data] = $this->groupService->create($user_id, [
            'name' => $params['group_name'],
            'avatar' => $params['avatar'] ?? '',
            'profile' => $params['group_profile'] ?? ''
        ], $friend_ids);

        if (!$isTrue) {
            return $this->response->fail('创建群聊失败，请稍后再试...');
        }

        // 加入聊天室
        $friend_ids[] = $user_id;
        foreach ($friend_ids as $uid) {
            $this->socketRoomService->addRoomMember($uid, $data['group_id']);
        }

        // ...消息推送队列
        $this->producer->produce(
            new ChatMessageProducer(SocketConstants::EVENT_TALK, [
                'sender' => $user_id,  //发送者ID
                'receive' => intval($data['group_id']),  //接收者ID
                'source' => 2, //接收者类型 1:好友;2:群组
                'record_id' => intval($data['record_id'])
            ])
        );

        return $this->response->success([
            'group_id' => $data['group_id']
        ]);
    }

    /**
     * 解散群组接口
     *
     * @RequestMapping(path="dismiss", methods="post")
     */
    public function dismiss()
    {
        $params = $this->request->inputs(['group_id']);
        $this->validate($params, [
            'group_id' => 'required|integer'
        ]);

        $isTrue = $this->groupService->dismiss($params['group_id'], $this->uid());
        if (!$isTrue) {
            return $this->response->fail('群组解散失败...');
        }

        $this->socketRoomService->delRoom($params['group_id']);

        // ... 推送群消息(后期添加)

        return $this->response->success([], '群组解散成功...');
    }

    /**
     * 邀请好友加入群组接口
     *
     * @RequestMapping(path="invite", methods="post")
     */
    public function invite()
    {
        $params = $this->request->inputs(['group_id', 'uids']);
        $this->validate($params, [
            'group_id' => 'required|integer',
            'uids' => 'required|ids'
        ]);

        $uids = parse_ids($params['uids']);

        $user_id = $this->uid();
        [$isTrue, $record_id] = $this->groupService->invite($user_id, $params['group_id'], $uids);
        if (!$isTrue) {
            return $this->response->fail('邀请好友加入群聊失败...');
        }

        // 加入聊天室
        foreach ($uids as $uid) {
            $this->socketRoomService->addRoomMember($uid, $params['group_id']);
        }

        // ...消息推送队列
        $this->producer->produce(
            new ChatMessageProducer(SocketConstants::EVENT_TALK, [
                'sender' => $user_id,  //发送者ID
                'receive' => intval($params['group_id']),  //接收者ID
                'source' => 2, //接收者类型 1:好友;2:群组
                'record_id' => $record_id
            ])
        );

        return $this->response->success([], '好友已成功加入群聊...');
    }

    /**
     * 退出群组接口
     *
     * @RequestMapping(path="secede", methods="post")
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
            return $this->response->fail('退出群组失败...');
        }

        // 移出聊天室
        $this->socketRoomService->delRoomMember($params['group_id'], $user_id);

        // ...消息推送队列
        $this->producer->produce(
            new ChatMessageProducer(SocketConstants::EVENT_TALK, [
                'sender' => $user_id,  //发送者ID
                'receive' => intval($params['group_id']),  //接收者ID
                'source' => 2, //接收者类型 1:好友;2:群组
                'record_id' => $record_id
            ])
        );

        return $this->response->success([], '已成功退出群组...');
    }

    /**
     * 编辑群组信息
     *
     * @RequestMapping(path="edit", methods="post")
     */
    public function editDetail()
    {
        $params = $this->request->inputs(['group_id', 'group_name', 'group_profile', 'avatar']);
        $this->validate($params, [
            'group_id' => 'required|integer',
            'group_name' => 'required',
            'group_profile' => 'required',
            'avatar' => 'present|url'
        ]);

        $result = UsersGroup::where('id', $params['group_id'])->where('user_id', $this->uid())->update([
            'group_name' => $params['group_name'],
            'group_profile' => $params['group_profile'],
            'avatar' => $params['avatar']
        ]);

        // 推送消息通知
        // ...

        return $result
            ? $this->response->success([], '群组信息修改成功...')
            : $this->response->fail('群组信息修改失败...');
    }

    /**
     * 移除指定成员（管理员权限）
     *
     * @RequestMapping(path="remove-members", methods="post")
     */
    public function removeMembers()
    {
        $params = $this->request->inputs(['group_id', 'members_ids']);
        $this->validate($params, [
            'group_id' => 'required|integer',
            'members_ids' => 'required|array'
        ]);

        $user_id = $this->uid();
        if (in_array($user_id, $params['members_ids'])) {
            return $this->response->fail('群聊用户移除失败...');
        }

        [$isTrue, $record_id] = $this->groupService->removeMember($params['group_id'], $user_id, $params['members_ids']);
        if (!$isTrue) {
            return $this->response->fail('群聊用户移除失败...');
        }

        // 移出聊天室
        foreach ($params['members_ids'] as $uid) {
            $this->socketRoomService->delRoomMember($params['group_id'], $uid);
        }

        // ...消息推送队列
        $this->producer->produce(
            new ChatMessageProducer(SocketConstants::EVENT_TALK, [
                'sender' => $user_id,  //发送者ID
                'receive' => intval($params['group_id']),  //接收者ID
                'source' => 2, //接收者类型 1:好友;2:群组
                'record_id' => $record_id
            ])
        );

        return $this->response->success([], '已成功退出群组...');
    }

    /**
     * 获取群信息接口
     *
     * @RequestMapping(path="detail", methods="get")
     */
    public function detail()
    {
        $group_id = $this->request->input('group_id', 0);

        $user_id = $this->uid();
        $groupInfo = UsersGroup::leftJoin('users', 'users.id', '=', 'users_group.user_id')
            ->where('users_group.id', $group_id)->where('users_group.status', 0)->first([
                'users_group.id', 'users_group.user_id',
                'users_group.group_name',
                'users_group.group_profile', 'users_group.avatar',
                'users_group.created_at',
                'users.nickname'
            ]);

        if (!$groupInfo) {
            return $this->response->success([]);
        }

        $notice = UsersGroupNotice::where('group_id', $group_id)
            ->where('is_delete', 0)
            ->orderBy('id', 'desc')
            ->first(['title', 'content']);

        return $this->response->success([
            'group_id' => $groupInfo->id,
            'group_name' => $groupInfo->group_name,
            'group_profile' => $groupInfo->group_profile,
            'avatar' => $groupInfo->avatar,
            'created_at' => $groupInfo->created_at,
            'is_manager' => $groupInfo->user_id == $user_id,
            'manager_nickname' => $groupInfo->nickname,
            'visit_card' => UsersGroupMember::visitCard($user_id, $group_id),
            'not_disturb' => UsersChatList::where('uid', $user_id)->where('group_id', $group_id)->where('type', 2)->value('not_disturb') ?? 0,
            'notice' => $notice ? $notice->toArray() : []
        ]);
    }

    /**
     * 设置用户群名片
     *
     * @RequestMapping(path="set-group-card", methods="post")
     */
    public function setGroupCard()
    {
        $params = $this->request->inputs(['group_id', 'visit_card']);
        $this->validate($params, [
            'group_id' => 'required|integer',
            'visit_card' => 'required'
        ]);

        $isTrue = UsersGroupMember::where('group_id', $params['group_id'])
            ->where('user_id', $this->uid())
            ->where('status', 0)
            ->update(['visit_card' => $params['visit_card']]);

        return $isTrue
            ? $this->response->success([], '群名片修改成功...')
            : $this->response->error('群名片修改失败...');
    }

    /**
     * 获取用户可邀请加入群组的好友列表
     *
     * @RequestMapping(path="invite-friends", methods="get")
     */
    public function getInviteFriends()
    {
        $group_id = $this->request->input('group_id', 0);
        $friends = UsersFriend::getUserFriends($this->uid());
        if ($group_id > 0 && $friends) {
            if ($ids = UsersGroupMember::getGroupMemberIds($group_id)) {
                foreach ($friends as $k => $item) {
                    if (in_array($item['id'], $ids)) unset($friends[$k]);
                }
            }

            $friends = array_values($friends);
        }

        return $this->response->success($friends);
    }

    /**
     * 获取群组成员列表
     *
     * @RequestMapping(path="members", methods="get")
     */
    public function getGroupMembers()
    {
        $user_id = $this->uid();
        $group_id = $this->request->input('group_id', 0);

        // 判断用户是否是群成员
        if (!UsersGroup::isMember($group_id, $user_id)) {
            return $this->response->fail('非法操作...');
        }

        $members = UsersGroupMember::select([
            'users_group_member.id', 'users_group_member.group_owner as is_manager', 'users_group_member.visit_card',
            'users_group_member.user_id', 'users.avatar', 'users.nickname', 'users.gender',
            'users.motto',
        ])
            ->leftJoin('users', 'users.id', '=', 'users_group_member.user_id')
            ->where([
                ['users_group_member.group_id', '=', $group_id],
                ['users_group_member.status', '=', 0],
            ])->orderBy('is_manager', 'desc')->get()->toArray();

        return $this->response->success($members);
    }

    /**
     * 获取群组公告列表
     *
     * @RequestMapping(path="notices", methods="get")
     */
    public function getGroupNotices()
    {
        $user_id = $this->uid();
        $group_id = $this->request->input('group_id', 0);

        // 判断用户是否是群成员
        if (!UsersGroup::isMember($group_id, $user_id)) {
            return $this->response->fail('非管理员禁止操作...');
        }

        $rows = UsersGroupNotice::leftJoin('users', 'users.id', '=', 'users_group_notice.user_id')
            ->where([
                ['users_group_notice.group_id', '=', $group_id],
                ['users_group_notice.is_delete', '=', 0]
            ])
            ->orderBy('users_group_notice.id', 'desc')
            ->get([
                'users_group_notice.id',
                'users_group_notice.user_id',
                'users_group_notice.title',
                'users_group_notice.content',
                'users_group_notice.created_at',
                'users_group_notice.updated_at',
                'users.avatar', 'users.nickname',
            ])->toArray();

        return $this->response->success($rows);
    }

    /**
     * 创建/编辑群公告
     *
     * @RequestMapping(path="edit-notice", methods="post")
     */
    public function editNotice()
    {
        $params = $this->request->inputs(['group_id', 'notice_id', 'title', 'content']);
        $this->validate($params, [
            'group_id' => 'required|integer',
            'notice_id' => 'required|integer',
            'title' => 'required',
            'content' => 'required'
        ]);

        $user_id = $this->uid();

        // 判断用户是否是管理员
        if (!UsersGroup::isManager($user_id, $params['group_id'])) {
            return $this->response->fail('非管理员禁止操作...');
        }

        // 判断是否是新增数据
        if (empty($data['notice_id'])) {
            $result = UsersGroupNotice::create([
                'group_id' => $params['group_id'],
                'title' => $params['title'],
                'content' => $params['content'],
                'user_id' => $user_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!$result) {
                return $this->response->fail('添加群公告信息失败...');
            }

            // ... 推送群消息
            return $this->response->success([], '添加群公告信息成功...');
        }

        $result = UsersGroupNotice::where('id', $data['notice_id'])->update([
            'title' => $data['title'],
            'content' => $data['content'],
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $result
            ? $this->response->success([], '修改群公告信息成功...')
            : $this->response->fail('修改群公告信息成功...');
    }

    /**
     * 删除群公告(软删除)
     *
     * @RequestMapping(path="delete-notice", methods="post")
     */
    public function deleteNotice()
    {
        $params = $this->request->inputs(['group_id', 'notice_id']);
        $this->validate($params, [
            'group_id' => 'required|integer',
            'notice_id' => 'required|integer'
        ]);

        $user_id = $this->uid();

        // 判断用户是否是管理员
        if (!UsersGroup::isManager($user_id, $params['group_id'])) {
            return $this->response->fail('非法操作...');
        }

        $result = UsersGroupNotice::where('id', $params['notice_id'])
            ->where('group_id', $params['group_id'])
            ->update([
                'is_delete' => 1,
                'deleted_at' => date('Y-m-d H:i:s')
            ]);

        return $result
            ? $this->response->success([], '公告删除成功...')
            : $this->response->fail('公告删除失败...');
    }
}
