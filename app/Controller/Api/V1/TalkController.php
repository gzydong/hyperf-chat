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

use App\Cache\LastMessage;
use App\Cache\Repository\LockRedis;
use App\Cache\UnreadTalkCache;
use App\Constants\TalkMessageType;
use App\Constants\TalkModeConstant;
use App\Model\Talk\TalkList;
use App\Service\Group\GroupMemberService;
use App\Service\UserFriendService;
use App\Support\UserRelation;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use Psr\Http\Message\ResponseInterface;
use App\Model\User;
use App\Model\Group\Group;
use App\Service\TalkService;
use App\Service\TalkListService;

/**
 * Class TalkController
 * @Controller(prefix="/api/v1/talk")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class TalkController extends CController
{
    /**
     * @Inject
     * @var TalkService
     */
    public $talkService;

    /**
     * @Inject
     * @var TalkListService
     */
    public $talkListService;

    /**
     * 获取用户对话列表
     * @RequestMapping(path="list", methods="get")
     *
     * @return ResponseInterface
     */
    public function list()
    {
        $user_id = $this->uid();

        // 读取用户的未读消息列表
        if ($list = UnreadTalkCache::getInstance()->reads($user_id)) {
            foreach ($list as $friend_id => $num) {
                $this->talkListService->create($user_id, $friend_id, TalkModeConstant::PRIVATE_CHAT);
            }
        }

        return $this->response->success($this->talkListService->getTalkList($user_id));
    }

    /**
     * 新增对话列表
     * @RequestMapping(path="create", methods="post")
     *
     * @return ResponseInterface
     */
    public function create(UserFriendService $service)
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id']);
        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1'
        ]);

        $user_id = $this->uid();
        $string  = join('-', [$user_id, $params['receiver_id'], $params['talk_type'], md5($this->request->getHeaderLine('user-agent'))]);
        $lock    = 'talk:list:' . $string;

        // 防止前端并发请求
        if (!LockRedis::getInstance()->lock($lock, 60)) {
            return $this->response->fail('创建失败！');
        }

        if (!UserRelation::isFriendOrGroupMember($user_id, $params['receiver_id'], $params['talk_type'])) {
            LockRedis::getInstance()->delete($lock);
            return $this->response->fail('暂不属于好友关系或群聊成员，无法进行聊天！');
        }

        $result = $this->talkListService->create($user_id, $params['receiver_id'], $params['talk_type']);
        if (!$result) {
            LockRedis::getInstance()->delete($lock);
            return $this->response->fail('创建失败！');
        }

        $data = TalkList::item([
            'id'          => $result['id'],
            'talk_type'   => $result['talk_type'],
            'receiver_id' => $result['receiver_id'],
        ]);

        if ($result['talk_type'] == TalkModeConstant::PRIVATE_CHAT) {
            $userInfo            = User::where('id', $data['receiver_id'])->first(['nickname', 'avatar']);
            $data['avatar']      = $userInfo->avatar;
            $data['name']        = $userInfo->nickname;
            $data['unread_num']  = UnreadTalkCache::getInstance()->read($data['receiver_id'], $user_id);
            $data['remark_name'] = $service->getFriendRemark($user_id, (int)$data['receiver_id']);
        } else if ($result['talk_type'] == TalkModeConstant::GROUP_CHAT) {
            $groupInfo      = Group::where('id', $data['receiver_id'])->first(['group_name', 'avatar']);
            $data['name']   = $groupInfo->group_name;
            $data['avatar'] = $groupInfo->avatar;
        }

        $records = LastMessage::getInstance()->read($result['talk_type'], $user_id, $result['receiver_id']);
        if ($records) {
            $data['msg_text']   = $records['text'];
            $data['updated_at'] = $records['created_at'];
        }

        return $this->response->success($data);
    }

    /**
     * 删除对话列表
     * @RequestMapping(path="delete", methods="post")
     *
     * @return ResponseInterface
     */
    public function delete()
    {
        $params = $this->request->inputs(['list_id']);
        $this->validate($params, [
            'list_id' => 'required|integer|min:0'
        ]);

        return $this->talkListService->delete($this->uid(), $params['list_id'])
            ? $this->response->success([], '对话列表删除成功...')
            : $this->response->fail('对话列表删除失败！');
    }

    /**
     * 对话列表置顶
     * @RequestMapping(path="topping", methods="post")
     *
     * @return ResponseInterface
     */
    public function topping()
    {
        $params = $this->request->inputs(['list_id', 'type']);
        $this->validate($params, [
            'list_id' => 'required|integer|min:0',
            'type'    => 'required|in:1,2',
        ]);

        return $this->talkListService->top($this->uid(), $params['list_id'], $params['type'] == 1)
            ? $this->response->success([], '对话列表置顶(或取消置顶)成功...')
            : $this->response->fail('对话列表置顶(或取消置顶)失败！');
    }

    /**
     * 设置消息免打扰状态
     * @RequestMapping(path="disturb", methods="post")
     *
     * @return ResponseInterface
     */
    public function disturb()
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'is_disturb']);
        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
            'is_disturb'  => 'required|in:0,1',
        ]);

        return $this->talkListService->disturb($this->uid(), $params['receiver_id'], $params['talk_type'], $params['is_disturb'])
            ? $this->response->success([], '免打扰设置成功...')
            : $this->response->fail('免打扰设置失败！');
    }

    /**
     * 更新对话列表未读数
     * @RequestMapping(path="update-unread-num", methods="post")
     *
     * @return ResponseInterface
     */
    public function updateUnreadNum()
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id']);
        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
        ]);

        // 设置好友消息未读数
        if ($params['talk_type'] == TalkModeConstant::PRIVATE_CHAT) {
            UnreadTalkCache::getInstance()->reset((int)$params['receiver_id'], $this->uid());
        }

        return $this->response->success();
    }

    /**
     * 获取对话面板中的聊天记录
     * @RequestMapping(path="records", methods="get")
     *
     * @return ResponseInterface
     */
    public function getChatRecords()
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'record_id']);
        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
            'record_id'   => 'required|integer|min:0',
        ]);

        $user_id = $this->uid();

        if ($params['talk_type'] == TalkModeConstant::GROUP_CHAT && !di()->get(GroupMemberService::class)->isMember((int)$params['receiver_id'], $user_id)) {
            return $this->response->fail('暂不属于好友关系或群聊成员，无法查看聊天记录！');
        }

        $limit  = 30;
        $result = $this->talkService->getChatRecords(
            $user_id,
            $params['receiver_id'],
            $params['talk_type'],
            $params['record_id'],
            $limit
        );

        return $this->response->success([
            'rows'      => $result,
            'record_id' => $result ? end($result)['id'] : 0,
            'limit'     => $limit
        ]);
    }

    /**
     * 获取转发记录详情
     * @RequestMapping(path="get-forward-records", methods="get")
     *
     * @return ResponseInterface
     */
    public function getForwardRecords()
    {
        $params = $this->request->inputs(['record_id']);
        $this->validate($params, [
            'record_id' => 'required|integer|min:1'
        ]);

        $rows = $this->talkService->getForwardRecords($this->uid(), $params['record_id']);

        return $this->response->success(['rows' => $rows]);
    }

    /**
     * 查询聊天记录
     * @RequestMapping(path="find-chat-records", methods="get")
     *
     * @return ResponseInterface
     */
    public function findChatRecords()
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'record_id', 'msg_type']);
        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
            'record_id'   => 'required|integer|min:0',
            'msg_type'    => 'required|integer',
        ]);

        $user_id = $this->uid();
        if ($params['talk_type'] == TalkModeConstant::GROUP_CHAT && !di()->get(GroupMemberService::class)->isMember((int)$params['receiver_id'], $user_id)) {
            return $this->response->fail('暂不属于好友关系或群聊成员，无法查看聊天记录！');
        }

        $types = [
            TalkMessageType::TEXT_MESSAGE,
            TalkMessageType::FILE_MESSAGE,
            TalkMessageType::FORWARD_MESSAGE,
            TalkMessageType::CODE_MESSAGE
        ];

        if (in_array($params['msg_type'], $types)) {
            $msg_type = [$params['msg_type']];
        } else {
            $msg_type = $types;
        }

        $limit  = 30;
        $result = $this->talkService->getChatRecords(
            $user_id,
            $params['receiver_id'],
            $params['talk_type'],
            $params['record_id'],
            $limit,
            $msg_type
        );

        return $this->response->success([
            'rows'      => $result,
            'record_id' => $result ? end($result)['id'] : 0,
            'limit'     => $limit
        ]);
    }
}
