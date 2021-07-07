<?php


namespace App\Controller\Api\V1;

use App\Amqp\Producer\ChatMessageProducer;
use App\Cache\FriendApply;
use App\Constants\SocketConstants;
use App\Model\UsersFriendsApply;
use App\Service\SocketClientService;
use App\Service\UserService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\Di\Annotation\Inject;
use App\Service\ContactApplyService;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ContactsApplyController
 * @Controller(prefix="/api/v1/contacts/apply")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class ContactsApplyController extends CController
{
    /**
     * @Inject
     * @var ContactApplyService
     */
    private $service;

    /**
     * @inject
     * @var SocketClientService
     */
    private $socketClientService;

    /**
     * @RequestMapping(path="create", methods="post")
     * @param UserService $userService
     * @return ResponseInterface
     */
    public function create(UserService $userService)
    {
        $params = $this->request->inputs(['friend_id', 'remarks']);
        $this->validate($params, [
            'friend_id' => 'required|integer',
            'remarks'   => 'present|max:50'
        ]);

        $params['friend_id'] = (int)$params['friend_id'];

        $user = $userService->findById($params['friend_id']);
        if (!$user) {
            return $this->response->fail('用户不存在！');
        }

        $user_id = $this->uid();

        [$isTrue, $result] = $this->service->create($user_id, $params['friend_id'], $params['remarks']);
        if (!$isTrue) {
            return $this->response->fail('添加好友申请失败！');
        }

        // 好友申请未读消息数自增
        FriendApply::getInstance()->incr($params['friend_id'], 1);

        // 判断对方是否在线。如果在线发送消息通知
        if ($this->socketClientService->isOnlineAll($params['friend_id'])) {
            push_amqp(new ChatMessageProducer(SocketConstants::EVENT_FRIEND_APPLY, [
                'apply_id' => $result->id,
                'type'     => 1,
            ]));
        }

        return $this->response->success([], '发送好友申请成功...');
    }

    /**
     * @RequestMapping(path="accept", methods="post")
     * @return ResponseInterface
     */
    public function accept()
    {
        $params = $this->request->inputs(['apply_id', 'remarks']);
        $this->validate($params, [
            'apply_id' => 'required|integer',
            'remarks'  => 'present|max:20'
        ]);

        $user_id = $this->uid();
        $isTrue  = $this->service->accept($user_id, intval($params['apply_id']), $params['remarks']);
        if (!$isTrue) {
            return $this->response->fail('处理失败！');
        }

        $friend_id = UsersFriendsApply::where('id', $params['apply_id'])->where('friend_id', $user_id)->value('user_id');

        // 判断对方是否在线。如果在线发送消息通知
        if ($this->socketClientService->isOnlineAll($friend_id)) {
            push_amqp(new ChatMessageProducer(SocketConstants::EVENT_FRIEND_APPLY, [
                'apply_id' => (int)$params['apply_id'],
                'type'     => 2,
            ]));
        }

        return $this->response->success([], '处理成功...');
    }

    /**
     * @RequestMapping(path="decline", methods="post")
     * @return ResponseInterface
     */
    public function decline()
    {
        $params = $this->request->inputs(['apply_id', 'remarks']);
        $this->validate($params, [
            'apply_id' => 'required|integer',
            'remarks'  => 'present|max:20'
        ]);

        $isTrue = $this->service->decline($this->uid(), intval($params['apply_id']), $params['remarks']);
        return $isTrue
            ? $this->response->success()
            : $this->response->fail();
    }

    /**
     * @RequestMapping(path="delete", methods="post")
     * @return ResponseInterface
     */
    public function delete()
    {
        $params = $this->request->inputs(['apply_id']);
        $this->validate($params, [
            'apply_id' => 'required|integer'
        ]);

        return $this->service->delete($this->uid(), intval($params['apply_id']))
            ? $this->response->success()
            : $this->response->fail();
    }

    /**
     * 获取联系人申请未读数
     * @RequestMapping(path="records", methods="get")
     *
     * @return ResponseInterface
     */
    public function records()
    {
        $params = $this->request->inputs(['page', 'page_size']);
        $this->validate($params, [
            'page'      => 'present|integer',
            'page_size' => 'present|integer'
        ]);

        $page      = $this->request->input('page', 1);
        $page_size = $this->request->input('page_size', 10);
        $user_id   = $this->uid();

        $data = $this->service->getApplyRecords($user_id, $page, $page_size);

        FriendApply::getInstance()->rem(strval($user_id));

        return $this->response->success($data);
    }
}
