<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Group;

use App\Controller\Api\V1\CController;
use App\Service\Group\GroupMemberService;
use App\Service\Group\GroupService;
use Hyperf\Di\Annotation\Inject;
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
     * @Inject
     * @var GroupService
     */
    private $groupService;

    /**
     * @inject
     * @var GroupMemberService
     */
    private $groupMemberService;

    /**
     * 获取群组列表
     * @RequestMapping(path="list", methods="get")
     */
    public function list(): ResponseInterface
    {
        return $this->response->success([
            "rows" => $this->groupService->getUserGroups($this->uid()),
        ]);
    }

    // public function Detail()
    // {
    //
    // }
    //
    // public function Create()
    // {
    //
    // }
    //
    // public function Dismiss()
    // {
    //
    // }
    //
    // public function Invite()
    // {
    //
    // }
    //
    // public function SignOut()
    // {
    //
    // }
    //
    // public function Setting()
    // {
    //
    // }
}
