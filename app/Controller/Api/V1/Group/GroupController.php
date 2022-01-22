<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Group;

use App\Constant\TalkModeConstant;
use App\Controller\Api\V1\CController;
use App\Model\Group\Group;
use App\Model\Group\GroupNotice;
use App\Service\Group\GroupMemberService;
use App\Service\Group\GroupService;
use App\Service\TalkSessionService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use Psr\Http\Message\ResponseInterface;

/**
 * Class GroupController
 *
 * @Controller(prefix="/api/v1/group")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1\Group
 */
class GroupController extends CController
{
    /**
     * @var GroupService
     */
    private $groupService;

    /**
     * @var GroupMemberService
     */
    private $groupMemberService;

    public function __construct(GroupService $groupService, GroupMemberService $groupMemberService)
    {
        parent::__construct();

        $this->groupService       = $groupService;
        $this->groupMemberService = $groupMemberService;
    }

    /**
     * 获取群组列表
     *
     * @RequestMapping(path="list", methods="get")
     */
    public function list(): ResponseInterface
    {
        return $this->response->success([
            "rows" => $this->groupService->getUserGroups($this->uid()),
        ]);
    }

    /**
     * 创建群组
     *
     * @RequestMapping(path="create", methods="post")
     */
    public function create(): ResponseInterface
    {
        $params = $this->request->inputs(['name', 'ids']);

        $this->validate($params, [
            'name' => 'required',
            'ids'  => 'required|ids'
        ]);

        [$isTrue, $group] = $this->groupService->create($this->uid(), [
            'name'    => $params['name'],
            'avatar'  => $params['avatar'] ?? '',
            'profile' => $params['profile'] ?? ''
        ], parse_ids($params['ids']));

        if (!$isTrue) return $this->response->fail('创建群聊失败，请稍后再试！');

        return $this->response->success(['group_id' => $group->id]);
    }

    /**
     * 解散群组接口
     *
     * @RequestMapping(path="dismiss", methods="post")
     */
    public function dismiss(): ResponseInterface
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
     *
     * @RequestMapping(path="invite", methods="post")
     */
    public function invite(): ResponseInterface
    {
        $params = $this->request->inputs(['group_id', 'ids']);

        $this->validate($params, [
            'group_id' => 'required|integer',
            'ids'      => 'required|ids'
        ]);

        $isTrue = $this->groupService->invite($this->uid(), intval($params['group_id']), parse_ids($params['ids']));
        if (!$isTrue) {
            return $this->response->fail('邀请好友加入群聊失败！');
        }

        return $this->response->success([], '好友已成功加入群聊...');
    }

    /**
     * 退出群组接口
     *
     * @RequestMapping(path="secede", methods="post")
     */
    public function secede(): ResponseInterface
    {
        $params = $this->request->inputs(['group_id']);

        $this->validate($params, [
            'group_id' => 'required|integer'
        ]);

        if (!$this->groupService->quit($this->uid(), intval($params['group_id']))) {
            return $this->response->fail('退出群组失败！');
        }

        return $this->response->success([], '已成功退出群组...');
    }

    /**
     * 获取群信息接口
     *
     * @RequestMapping(path="detail", methods="get")
     */
    public function detail(TalkSessionService $service): ResponseInterface
    {
        $group_id = (int)$this->request->input('group_id', 0);
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
            'visit_card'       => $this->groupMemberService->getVisitCard($group_id, $user_id),
            'is_disturb'       => (int)$service->isDisturb($user_id, $group_id, TalkModeConstant::GROUP_CHAT),
            'notice'           => $notice ? $notice->toArray() : []
        ]);
    }

    /**
     * 编辑群组信息
     *
     * @RequestMapping(path="setting", methods="post")
     */
    public function Setting(): ResponseInterface
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
}
