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

use App\Cache\FriendApply;
use App\Cache\FriendRemark;
use App\Model\UsersFriendsApply;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use Psr\Http\Message\ResponseInterface;
use App\Amqp\Producer\ChatMessageProducer;
use App\Service\ContactsService;
use App\Service\SocketClientService;
use App\Service\UserService;
use App\Cache\ApplyNumCache;
use App\Cache\FriendRemarkCache;
use App\Model\UsersChatList;
use App\Constants\SocketConstants;

/**
 * Class ContactsController
 * @Controller(path="/api/v1/contacts")
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
    private $contactsService;

    /**
     * @inject
     * @var SocketClientService
     */
    private $socketClientService;

    /**
     * 获取用户联系人列表
     * @RequestMapping(path="list", methods="get")
     *
     * @return ResponseInterface
     */
    public function getContacts()
    {
        $rows = $this->contactsService->getContacts($this->uid());
        if ($rows) {
            $runArr = $this->socketClientService->getServerRunIdAll();
            foreach ($rows as $k => $row) {
                // 查询用户当前是否在线
                $rows[$k]['online'] = $this->socketClientService->isOnlineAll($row['id'], $runArr);
            }
        }

        return $this->response->success($rows);
    }

    /**
     * 添加联系人
     * @RequestMapping(path="add", methods="post")
     *
     * @param UserService $userService
     * @return ResponseInterface
     */
    public function addContact(UserService $userService)
    {
        $params = $this->request->inputs(['friend_id', 'remarks']);
        $this->validate($params, [
            'friend_id' => 'required|integer',
            'remarks'   => 'present|max:50'
        ]);

        $user = $userService->findById($params['friend_id']);
        if (!$user) {
            return $this->response->fail('用户不存在！');
        }

        $user_id = $this->uid();
        if (!$this->contactsService->addContact($user_id, intval($params['friend_id']), $params['remarks'])) {
            return $this->response->fail('添加好友申请失败！');
        }

        // 好友申请未读消息数自增
        FriendApply::getInstance()->incr($params['friend_id'], 1);

        // 判断对方是否在线。如果在线发送消息通知
        if ($this->socketClientService->isOnlineAll(intval($params['friend_id']))) {
            push_amqp(new ChatMessageProducer(SocketConstants::EVENT_FRIEND_APPLY, [
                'sender'  => $user_id,
                'receive' => intval($params['friend_id']),
                'type'    => 1,
                'status'  => 1,
                'remark'  => ''
            ]));
        }

        return $this->response->success([], '发送好友申请成功...');
    }

    /**
     * 删除联系人
     * @RequestMapping(path="delete", methods="post")
     *
     * @return ResponseInterface
     */
    public function deleteContact()
    {
        $params = $this->request->inputs(['friend_id']);
        $this->validate($params, [
            'friend_id' => 'required|integer'
        ]);

        $user_id = $this->uid();
        if (!$this->contactsService->deleteContact($user_id, intval($params['friend_id']))) {
            return $this->response->fail('好友关系解除失败！');
        }

        // 删除好友会话列表
        UsersChatList::delItem($user_id, $params['friend_id'], 2);
        UsersChatList::delItem($params['friend_id'], $user_id, 2);

        // ... TODO 推送消息（待完善）

        return $this->response->success([], '好友关系解除成功...');
    }

    /**
     * 同意添加联系人
     * @RequestMapping(path="accept-invitation", methods="post")
     *
     * @return ResponseInterface
     */
    public function acceptInvitation()
    {
        $params = $this->request->inputs(['apply_id', 'remarks']);
        $this->validate($params, [
            'apply_id' => 'required|integer',
            'remarks'  => 'present|max:20'
        ]);

        $user_id = $this->uid();
        $isTrue  = $this->contactsService->acceptInvitation($user_id, intval($params['apply_id']), $params['remarks']);
        if (!$isTrue) {
            return $this->response->fail('处理失败！');
        }

        $friend_id = $info = UsersFriendsApply::where('id', $params['apply_id'])
            ->where('friend_id', $user_id)
            ->value('user_id');

        // 判断对方是否在线。如果在线发送消息通知
        if ($this->socketClientService->isOnlineAll($friend_id)) {
            // TODO 待完善
            push_amqp(new ChatMessageProducer(SocketConstants::EVENT_FRIEND_APPLY, [
                'sender'  => $user_id,
                'receive' => $friend_id,
                'type'    => 1,
                'status'  => 1,
                'remark'  => ''
            ]));
        }

        return $this->response->success([], '处理成功...');
    }

    /**
     * 拒绝添加联系人(预留)
     * @RequestMapping(path="decline-invitation", methods="post")
     *
     * @return ResponseInterface
     */
    public function declineInvitation()
    {
        $params = $this->request->inputs(['apply_id', 'remarks']);
        $this->validate($params, [
            'apply_id' => 'required|integer',
            'remarks'  => 'present|max:20'
        ]);

        $isTrue = $this->contactsService->declineInvitation($this->uid(), intval($params['apply_id']), $params['remarks']);

        return $isTrue
            ? $this->response->success()
            : $this->response->fail();
    }

    /**
     * 删除联系人申请记录
     * @RequestMapping(path="delete-apply", methods="post")
     *
     * @return ResponseInterface
     */
    public function deleteContactApply()
    {
        $params = $this->request->inputs(['apply_id']);
        $this->validate($params, [
            'apply_id' => 'required|integer'
        ]);

        $isTrue = $this->contactsService->delContactApplyRecord($this->uid(), intval($params['apply_id']));

        return $isTrue
            ? $this->response->success()
            : $this->response->fail();
    }

    /**
     * 获取联系人申请未读数
     * @RequestMapping(path="apply-unread-num", methods="get")
     *
     * @return ResponseInterface
     */
    public function getContactApplyUnreadNum()
    {
        return $this->response->success([
            'unread_num' => FriendApply::getInstance()->get(strval($this->uid()))
        ]);
    }

    /**
     * 获取联系人申请未读数
     * @RequestMapping(path="apply-records", methods="get")
     *
     * @return ResponseInterface
     */
    public function getContactApplyRecords()
    {
        $params = $this->request->inputs(['page', 'page_size']);
        $this->validate($params, [
            'page'      => 'present|integer',
            'page_size' => 'present|integer'
        ]);

        $page      = $this->request->input('page', 1);
        $page_size = $this->request->input('page_size', 10);
        $user_id   = $this->uid();

        $data = $this->contactsService->getContactApplyRecords($user_id, $page, $page_size);

        FriendApply::getInstance()->rem(strval($user_id));

        return $this->response->success($data);
    }

    /**
     * 搜索联系人
     * @RequestMapping(path="search", methods="get")
     *
     * @return ResponseInterface
     */
    public function searchContacts()
    {
        $params = $this->request->inputs(['mobile']);
        $this->validate($params, [
            'mobile' => "present|regex:/^1[3456789][0-9]{9}$/"
        ]);

        $result = $this->contactsService->findContact($params['mobile']);
        return $this->response->success($result);
    }

    /**
     * 编辑联系人备注
     * @RequestMapping(path="edit-remark", methods="post")
     *
     * @return ResponseInterface
     */
    public function editContactRemark()
    {
        $params = $this->request->inputs(['friend_id', 'remarks']);
        $this->validate($params, [
            'friend_id' => 'required|integer|min:1',
            'remarks'   => "required|max:20"
        ]);

        $user_id = $this->uid();
        $isTrue  = $this->contactsService->editContactRemark($user_id, intval($params['friend_id']), $params['remarks']);
        if (!$isTrue) {
            return $this->response->fail('备注修改失败！');
        }

        FriendRemark::getInstance()->save($user_id, (int)$params['friend_id'], $params['remarks']);

        return $this->response->success([], '备注修改成功...');
    }
}
