<?php

namespace App\Controller\Api\V1\Contact;

use App\Cache\FriendRemark;
use App\Cache\ServerRunID;
use App\Constant\TalkModeConstant;
use App\Controller\Api\V1\CController;
use App\Middleware\JWTAuthMiddleware;
use App\Repository\Contact\ContactRepository;
use App\Repository\UserRepository;
use App\Service\Contact\ContactsService;
use App\Service\SocketClientService;
use App\Service\TalkSessionService;
use App\Service\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ContactsController
 * @Controller(prefix="/api/v1/contact")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class ContactController extends CController
{
    /**
     * @Inject
     * @var ContactsService
     */
    private $service;

    /**
     * @Inject
     * @var UserService
     */
    private $userService;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * 获取用户联系人列表
     *
     * @RequestMapping(path="list", methods="get")
     */
    public function getContacts(UserService $service): ResponseInterface
    {


        $rows = di()->get(ContactRepository::class)->friends($this->uid());


        // $rows = $service->getUserFriends($this->uid());
        if ($rows) {
            $runArr = ServerRunID::getInstance()->getServerRunIdAll();
            foreach ($rows as $k => $row) {
                $rows[$k]['is_online'] = di()->get(SocketClientService::class)->isOnlineAll($row['id'], $runArr);
            }
        }

        return $this->response->success($rows);
    }

    /**
     * 删除联系人
     *
     * @RequestMapping(path="delete", methods="post")
     */
    public function deleteContact(): ResponseInterface
    {
        $params = $this->request->inputs(['friend_id']);
        $this->validate($params, [
            'friend_id' => 'required|integer'
        ]);

        $user_id = $this->uid();
        if (!$this->service->delete($user_id, intval($params['friend_id']))) {
            return $this->response->fail('好友关系解除失败！');
        }

        di()->get(TalkSessionService::class)->deleteByType($user_id, $params['friend_id'], TalkModeConstant::PRIVATE_CHAT);

        return $this->response->success([], '好友关系解除成功...');
    }

    /**
     * 搜索联系人
     *
     * @RequestMapping(path="search", methods="get")
     */
    public function search(): ResponseInterface
    {
        $params = $this->request->inputs(['mobile']);

        $this->validate($params, [
            'mobile' => "present|regex:/^1[3456789][0-9]{9}$/"
        ]);

        $result = $this->userRepository->findByMobile($params['mobile'], [
            'id', 'nickname', 'mobile', 'avatar', 'gender'
        ]);

        return $this->response->success($result);
    }

    /**
     * 编辑联系人备注
     *
     * @RequestMapping(path="edit-remark", methods="post")
     */
    public function editRemark(): ResponseInterface
    {
        $params = $this->request->inputs(['friend_id', 'remarks']);

        $this->validate($params, [
            'friend_id' => 'required|integer|min:1',
            'remarks'   => "required|max:20"
        ]);

        $user_id = $this->uid();
        $isTrue  = $this->service->editRemark($user_id, intval($params['friend_id']), $params['remarks']);
        if (!$isTrue) {
            return $this->response->fail('备注修改失败！');
        }

        FriendRemark::getInstance()->save($user_id, (int)$params['friend_id'], $params['remarks']);

        return $this->response->success([], '备注修改成功...');
    }

    /**
     * 通过用户ID查找用户
     *
     * @RequestMapping(path="detail", methods="get")
     *
     * @return ResponseInterface
     */
    public function detail(): ResponseInterface
    {
        $params = $this->request->inputs(['user_id']);

        $this->validate($params, ['user_id' => 'required|integer']);

        if ($data = $this->userService->getUserCard($params['user_id'], $this->uid())) {
            return $this->response->success($data);
        }

        return $this->response->fail('用户查询失败！');
    }
}
