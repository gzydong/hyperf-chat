<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Group;

use App\Controller\Api\V1\CController;
use App\Service\Group\GroupMemberService;
use App\Service\Group\GroupNoticeService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use Psr\Http\Message\ResponseInterface;

/**
 * Class NoticeController
 *
 * @Controller(prefix="/api/v1/group/notice")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1\Group
 */
class NoticeController extends CController
{
    /**
     * @var GroupMemberService
     */
    private $groupMemberService;

    public function __construct(GroupMemberService $groupMemberService)
    {
        parent::__construct();

        $this->groupMemberService = $groupMemberService;
    }

    /**
     * 获取群组公告列表
     *
     * @RequestMapping(path="list", methods="get")
     */
    public function list(GroupNoticeService $service): ResponseInterface
    {
        $user_id  = $this->uid();
        $group_id = (int)$this->request->input('group_id', 0);

        // 判断用户是否是群成员
        if (!$this->groupMemberService->isMember($group_id, $user_id)) {
            return $this->response->fail('非管理员禁止操作！');
        }

        return $this->response->success([
            "rows" => $service->lists($group_id)
        ]);
    }

    /**
     * 创建/编辑群公告
     * @RequestMapping(path="edit", methods="post")
     */
    public function edit(GroupNoticeService $service): ResponseInterface
    {
        $params = $this->request->inputs([
            'group_id', 'notice_id', 'title', 'content', 'is_top', 'is_confirm'
        ]);

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
        if (!$this->groupMemberService->isAuth(intval($params['group_id']), $user_id)) {
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
     * 删除群公告
     *
     * @RequestMapping(path="delete", methods="post")
     */
    public function delete(GroupNoticeService $service): ResponseInterface
    {
        $params = $this->request->inputs(['group_id', 'notice_id']);

        $this->validate($params, [
            'group_id'  => 'required|integer',
            'notice_id' => 'required|integer'
        ]);

        try {
            $isTrue = $service->delete(intval($params['notice_id']), $this->uid());
        } catch (\Exception $e) {
            return $this->response->fail($e->getMessage());
        }

        return $isTrue ? $this->response->success([], '公告删除成功...') : $this->response->fail('公告删除失败！');
    }
}
