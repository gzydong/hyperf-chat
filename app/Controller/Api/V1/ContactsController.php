<?php

namespace App\Controller\Api\V1;

use App\Constants\TalkModeConstant;
use App\Service\TalkListService;
use App\Service\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use Psr\Http\Message\ResponseInterface;
use App\Service\ContactsService;
use App\Service\SocketClientService;
use App\Cache\FriendApply;
use App\Cache\FriendRemark;
use App\Cache\ServerRunID;

/**
 * Class ContactsController
 * @Controller(prefix="/api/v1/contacts")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class ContactsController extends CController
{
    /**
     * @Inject
     * @var ContactsService
     */
    private $service;

    /**
     * 获取用户联系人列表
     *
     * @RequestMapping(path="list", methods="get")
     */
    public function getContacts(UserService $service): ResponseInterface
    {
        $rows = $service->getUserFriends($this->uid());
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

        di()->get(TalkListService::class)->deleteByType($user_id, $params['friend_id'], TalkModeConstant::PRIVATE_CHAT);

        // TODO 推送消息（待完善）

        return $this->response->success([], '好友关系解除成功...');
    }

    /**
     * 获取联系人申请未读数
     *
     * @RequestMapping(path="apply-unread-num", methods="get")
     */
    public function getContactApplyUnreadNum(): ResponseInterface
    {
        return $this->response->success([
            'unread_num' => (int)FriendApply::getInstance()->get(strval($this->uid()))
        ]);
    }

    /**
     * 搜索联系人
     *
     * @RequestMapping(path="search", methods="get")
     */
    public function searchContacts(): ResponseInterface
    {
        $params = $this->request->inputs(['mobile']);
        $this->validate($params, [
            'mobile' => "present|regex:/^1[3456789][0-9]{9}$/"
        ]);

        $result = $this->service->findContact($params['mobile']);
        return $this->response->success($result);
    }

    /**
     * 编辑联系人备注
     *
     * @RequestMapping(path="edit-remark", methods="post")
     */
    public function editContactRemark(): ResponseInterface
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
}
