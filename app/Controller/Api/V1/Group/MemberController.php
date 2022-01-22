<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Group;

use App\Controller\Api\V1\CController;
use App\Model\Group\GroupMember;
use App\Repository\Contact\ContactRepository;
use App\Service\Group\GroupMemberService;
use App\Service\Group\GroupService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use Psr\Http\Message\ResponseInterface;

/**
 * Class MemberController
 *
 * @Controller(prefix="/api/v1/group/member")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1\Group
 */
class MemberController extends CController
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
     * 获取群组成员列表
     *
     * @RequestMapping(path="list", methods="get")
     */
    public function list(): ResponseInterface
    {
        $user_id  = $this->uid();
        $group_id = (int)$this->request->input('group_id', 0);

        // 判断用户是否是群成员
        if (!$this->groupMemberService->isMember($group_id, $user_id)) {
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
     * 获取可邀请加入群组的好友列表
     *
     * @RequestMapping(path="invites", methods="get")
     */
    public function invites(): ResponseInterface
    {
        $group_id = (int)$this->request->input('group_id', 0);

        $friends = di()->get(ContactRepository::class)->friends($this->uid());
        if ($group_id > 0 && $friends) {
            if ($ids = $this->groupMemberService->getMemberIds($group_id)) {
                foreach ($friends as $k => $item) {
                    if (in_array($item['id'], $ids)) unset($friends[$k]);
                }
            }

            $friends = array_values($friends);
        }

        return $this->response->success($friends);
    }


    /**
     * 移除指定成员（管理员权限）
     *
     * @RequestMapping(path="remove", methods="post")
     */
    public function removeMembers(): ResponseInterface
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

        $isTrue = $this->groupService->removeMember(intval($params['group_id']), $user_id, $params['members_ids']);
        if (!$isTrue) {
            return $this->response->fail('群聊用户移除失败！');
        }

        return $this->response->success([], '已成功退出群组...');
    }

    /**
     * 设置群名片
     *
     * @RequestMapping(path="remark", methods="post")
     */
    public function remark(): ResponseInterface
    {
        $params = $this->request->inputs(['group_id', 'visit_card']);

        $this->validate($params, [
            'group_id'   => 'required|integer',
            'visit_card' => 'required|max:20'
        ]);

        $isTrue = $this->groupService->updateMemberCard(intval($params['group_id']), $this->uid(), $params['visit_card']);

        return $isTrue ? $this->response->success([], '群名片修改成功...') : $this->response->fail('群名片修改失败！');
    }
}
