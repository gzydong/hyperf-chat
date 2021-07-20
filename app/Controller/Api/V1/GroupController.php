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

use App\Constants\TalkModeConstant;
use App\Service\GroupNoticeService;
use App\Service\TalkListService;
use App\Service\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use App\Model\Group\Group;
use App\Model\Group\GroupMember;
use App\Model\Group\GroupNotice;
use App\Service\GroupService;
use Psr\Http\Message\ResponseInterface;

/**
 * Class GroupController
 * @Controller(prefix="/api/v1/group")
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

        [$isTrue, $group] = $this->groupService->create($this->uid(), [
            'name'    => $params['group_name'],
            'avatar'  => $params['avatar'] ?? '',
            'profile' => $params['group_profile'] ?? ''
        ], parse_ids($params['uids']));

        if (!$isTrue) return $this->response->fail('创建群聊失败，请稍后再试！');

        return $this->response->success([
            'group_id' => $group->id
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

        $isTrue = $this->groupService->invite($this->uid(), $params['group_id'], parse_ids($params['uids']));
        if (!$isTrue) {
            return $this->response->fail('邀请好友加入群聊失败！');
        }

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

        if (!$this->groupService->quit($this->uid(), $params['group_id'])) {
            return $this->response->fail('退出群组失败！');
        }

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

        return $this->groupService->update($params['group_id'], $this->uid(), $params)
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

        $isTrue = $this->groupService->removeMember($params['group_id'], $user_id, $params['members_ids']);
        if (!$isTrue) {
            return $this->response->fail('群聊用户移除失败！');
        }

        return $this->response->success([], '已成功退出群组...');
    }

    /**
     * 获取群信息接口
     * @RequestMapping(path="detail", methods="get")
     *
     * @return ResponseInterface
     */
    public function detail(TalkListService $service)
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

        if (!$groupInfo) return $this->response->success();

        $notice = GroupNotice::where('group_id', $group_id)->where('is_delete', 0)->orderBy('id', 'desc')->first(['title', 'content']);

        return $this->response->success([
            'group_id'         => $groupInfo->id,
            'group_name'       => $groupInfo->group_name,
            'profile'          => $groupInfo->profile,
            'avatar'           => $groupInfo->avatar,
            'created_at'       => $groupInfo->created_at,
            'is_manager'       => $groupInfo->creator_id == $user_id,
            'manager_nickname' => $groupInfo->nickname,
            'visit_card'       => GroupMember::visitCard($user_id, $group_id),
            'is_disturb'       => (int)$service->isDisturb($user_id, $group_id, TalkModeConstant::GROUP_CHAT),
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

        $isTrue = $this->groupService->updateMemberCard($params['group_id'], $this->uid(), $params['visit_card']);

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
    public function getInviteFriends(UserService $service)
    {
        $group_id = $this->request->input('group_id', 0);
        $friends  = $service->getUserFriends($this->uid());
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
            $this->groupService->getUserGroups($this->uid())
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
    public function getGroupNotice(GroupNoticeService $service)
    {
        $user_id  = $this->uid();
        $group_id = $this->request->input('group_id', 0);

        // 判断用户是否是群成员
        if (!Group::isMember($group_id, $user_id)) {
            return $this->response->fail('非管理员禁止操作！');
        }

        return $this->response->success($service->lists($group_id));
    }

    /**
     * 创建/编辑群公告
     * @RequestMapping(path="edit-notice", methods="post")
     *
     * @return ResponseInterface
     */
    public function editNotice(GroupNoticeService $service)
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
            if (!$service->create($user_id, $params)) {
                return $this->response->fail('添加群公告信息失败！');
            }

            return $this->response->success([], '添加群公告信息成功...');
        }

        return $service->update($params)
            ? $this->response->success([], '修改群公告信息成功...')
            : $this->response->fail('修改群公告信息失败！');
    }

    /**
     * 删除群公告(软删除)
     * @RequestMapping(path="delete-notice", methods="post")
     *
     * @return ResponseInterface
     */
    public function deleteNotice(GroupNoticeService $service)
    {
        $params = $this->request->inputs(['group_id', 'notice_id']);
        $this->validate($params, [
            'group_id'  => 'required|integer',
            'notice_id' => 'required|integer'
        ]);

        try {
            $isTrue = $service->delete($params['notice_id'], $this->uid());
        } catch (\Exception $e) {
            return $this->response->fail($e->getMessage());
        }

        return $isTrue
            ? $this->response->success([], '公告删除成功...')
            : $this->response->fail('公告删除失败！');
    }
}
