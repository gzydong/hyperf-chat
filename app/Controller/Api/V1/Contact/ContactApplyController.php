<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Contact;

use App\Cache\FriendApply;
use App\Cache\Repository\LockRedis;
use App\Controller\Api\V1\CController;
use App\Middleware\JWTAuthMiddleware;
use App\Repository\UserRepository;
use App\Service\Contact\ContactApplyService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ContactsApplyController
 *
 * @Controller(prefix="/api/v1/contact/apply")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class ContactApplyController extends CController
{
    /**
     * @var ContactApplyService
     */
    private $service;

    public function __construct(ContactApplyService $service)
    {
        parent::__construct();

        $this->service = $service;
    }

    /**
     * 添加联系人申请接口
     *
     * @RequestMapping(path="create", methods="post")
     *
     * @return ResponseInterface
     */
    public function create(): ResponseInterface
    {
        $params = $this->request->inputs(['friend_id', 'remark']);
        $this->validate($params, [
            'friend_id' => 'required|integer',
            'remark'    => 'present|max:50'
        ]);

        $params['friend_id'] = (int)$params['friend_id'];

        $user = di()->get(UserRepository::class)->findById($params['friend_id']);

        if (!$user) {
            return $this->response->fail('用户不存在！');
        }

        $user_id = $this->uid();
        $key     = "{$user_id}_{$params['friend_id']}";

        if (LockRedis::getInstance()->lock($key, 10)) {
            $isTrue = $this->service->create($user_id, $params['friend_id'], $params['remark']);
            if ($isTrue) {
                return $this->response->success([], '发送好友申请成功...');
            } else {
                LockRedis::getInstance()->delete($key);
            }
        }

        return $this->response->fail('添加好友申请失败！');
    }

    /**
     * 好友同意接口
     *
     * @RequestMapping(path="accept", methods="post")
     */
    public function accept(): ResponseInterface
    {
        $params = $this->request->inputs(['apply_id', 'remark']);
        $this->validate($params, [
            'apply_id' => 'required|integer',
            'remark'   => 'present|max:20'
        ]);

        $user_id = $this->uid();
        $isTrue  = $this->service->accept($user_id, (int)$params['apply_id'], $params['remark']);
        if (!$isTrue) {
            return $this->response->fail('处理失败！');
        }

        return $this->response->success([], '处理成功...');
    }

    /**
     * 好友拒绝接口
     *
     * @RequestMapping(path="decline", methods="post")
     */
    public function decline(): ResponseInterface
    {
        $params = $this->request->inputs(['apply_id', 'remark']);
        $this->validate($params, [
            'apply_id' => 'required|integer',
            'remark'   => 'present|max:20'
        ]);

        $isTrue = $this->service->decline($this->uid(), (int)$params['apply_id'], $params['remark']);
        if (!$isTrue) {
            return $this->response->fail('处理失败！');
        }

        return $this->response->success([], '处理成功...');
    }

    /**
     * 获取联系人申请未读数
     *
     * @RequestMapping(path="records", methods="get")
     */
    public function records(): ResponseInterface
    {
        $params = $this->request->inputs(['page', 'page_size']);
        $this->validate($params, [
            'page'      => 'present|integer',
            'page_size' => 'present|integer'
        ]);

        $page      = (int)$this->request->input('page', 1);
        $page_size = (int)$this->request->input('page_size', 10);
        $user_id   = $this->uid();

        FriendApply::getInstance()->rem(strval($user_id));

        return $this->response->success(
            $this->service->getApplyRecords($user_id, $page, $page_size)
        );
    }

    /**
     * 获取联系人申请未读数
     *
     * @RequestMapping(path="unread-num", methods="get")
     */
    public function getContactApplyUnreadNum(): ResponseInterface
    {
        return $this->response->success([
            'unread_num' => (int)FriendApply::getInstance()->get(strval($this->uid()))
        ]);
    }
}
