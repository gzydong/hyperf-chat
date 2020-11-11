<?php

namespace App\Controller\Api\V1;

use App\Cache\LastMsgCache;
use App\Cache\UnreadTalkCache;
use App\Model\User;
use App\Model\UsersChatList;
use App\Model\UsersFriend;
use App\Model\Group\UsersGroup;
use App\Service\TalkService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Phper666\JWTAuth\Middleware\JWTAuthMiddleware;


/**
 * Class TalkController
 *
 * @Controller(path="/api/v1/talk")
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
     * @var UnreadTalkCache
     */
    public $unreadTalkCache;

    /**
     * 获取用户对话列表
     *
     * @RequestMapping(path="list", methods="get")
     */
    public function list()
    {
        $user_id = $this->uid();

        // 读取用户的未读消息列表
        $result = $this->unreadTalkCache->getAll($user_id);
        if ($result) {
            $this->talkService->updateUnreadTalkList($user_id, $result);
        }

        // 获取聊天列表
        $rows = $this->talkService->talks($user_id);
        if ($rows) {
            $rows = arraysSort($rows, 'updated_at');
        }

        return $this->response->success($rows);
    }

    /**
     * 新增对话列表
     *
     * @RequestMapping(path="create", methods="post")
     *
     * @param UnreadTalkCache $unreadTalkCache
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function create(UnreadTalkCache $unreadTalkCache)
    {
        $params = $this->request->inputs(['type', 'receive_id']);
        $this->validate($params, [
            'type' => 'required|in:1,2',
            'receive_id' => 'present|integer|min:0'
        ]);

        $user_id = $this->uid();
        if ($params['type'] == 1) {
            if (!UsersFriend::isFriend($user_id, $params['receive_id'])) {
                return $this->response->fail('暂不属于好友关系，无法进行聊天...');
            }
        } else {
            if (!UsersGroup::isMember($params['receive_id'], $user_id)) {
                return $this->response->fail('暂不属于群成员，无法进行群聊...');
            }
        }

        $result = UsersChatList::addItem($user_id, $params['receive_id'], $params['type']);
        if (!$result) {
            return $this->response->fail('创建失败...');
        }

        $data = [
            'id' => $result['id'],
            'type' => $result['type'],
            'group_id' => $result['group_id'],
            'friend_id' => $result['friend_id'],
            'is_top' => 0,
            'msg_text' => '',
            'not_disturb' => 0,
            'online' => 1,
            'name' => '',
            'remark_name' => '',
            'avatar' => '',
            'unread_num' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($result['type'] == 1) {
            $data['unread_num'] = $unreadTalkCache->get($user_id, $result['friend_id']);

            $userInfo = User::where('id', $user_id)->first(['nickname', 'avatar']);
            $data['name'] = $userInfo->nickname;
            $data['avatar'] = $userInfo->avatar;
        } else if ($result['type'] == 2) {
            $groupInfo = UsersGroup::where('id', $result['group_id'])->first(['group_name', 'avatar']);
            $data['name'] = $groupInfo->group_name;
            $data['avatar'] = $groupInfo->avatar;
        }

        $records = LastMsgCache::get($result['type'] == 1 ? $result['friend_id'] : $result['group_id'], $result['type'] == 1 ? $user_id : 0);
        if ($records) {
            $data['msg_text'] = $records['text'];
            $data['updated_at'] = $records['created_at'];
        }

        return $this->response->success(['talkItem' => $data]);
    }

    /**
     * 删除对话列表
     *
     * @RequestMapping(path="delete", methods="post")
     */
    public function delete()
    {
        $params = $this->request->inputs(['list_id']);
        $this->validate($params, [
            'list_id' => 'required|integer|min:0'
        ]);

        return UsersChatList::delItem($this->uid(), $params['list_id'])
            ? $this->response->success([], '对话列表删除成功...')
            : $this->response->fail('对话列表删除失败...');
    }

    /**
     * 对话列表置顶
     *
     * @RequestMapping(path="topping", methods="post")
     */
    public function topping()
    {
        $params = $this->request->inputs(['list_id', 'type']);
        $this->validate($params, [
            'list_id' => 'required|integer|min:0',
            'type' => 'required|in:1,2',
        ]);

        return UsersChatList::topItem($this->uid(), $params['list_id'], $params['type'] == 1)
            ? $this->response->success([], '对话列表置顶成功...')
            : $this->response->fail('对话列表置顶失败...');
    }

    /**
     * 设置消息免打扰状态
     *
     * @RequestMapping(path="set-not-disturb", methods="post")
     */
    public function setNotDisturb()
    {
        $params = $this->request->inputs(['list_id', 'type', 'not_disturb']);
        $this->validate($params, [
            'receive_id' => 'required|integer|min:0',
            'type' => 'required|in:1,2',
            'not_disturb' => 'required|in:0,1',
        ]);

        $isTrue = UsersChatList::notDisturbItem($this->uid(), $params['receive_id'], $params['type'], $params['not_disturb']);

        return $isTrue
            ? $this->response->success([], '对话列表置顶成功...')
            : $this->response->fail('对话列表置顶失败...');
    }

    /**
     * 更新对话列表未读数
     *
     * @RequestMapping(path="update-unread-num", methods="post")
     *
     * @param UnreadTalkCache $unreadTalkCache
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function updateUnreadNum(UnreadTalkCache $unreadTalkCache)
    {
        $params = $this->request->inputs(['receive', 'type']);
        $this->validate($params, [
            'receive' => 'required|integer|min:0',
            'type' => 'required|integer|min:0'
        ]);

        $user_id = $this->uid();

        // 设置好友消息未读数
        if ($params['type'] == 1) {
            $unreadTalkCache->del($user_id, $params['receive']);
        }

        return $this->response->success([], 'success');
    }

    /**
     * @RequestMapping(path="revoke-records", methods="post")
     */
    public function revokeChatRecords()
    {
        $user_id = $this->uid();
        $record_id = $this->request->get('record_id', 0);

        [$isTrue, $message, $data] = $this->talkService->revokeRecord($user_id, $record_id);


        return $isTrue ? $this->ajaxSuccess($message) : $this->ajaxError($message);
    }

    /**
     * 删除聊天记录
     *
     * @RequestMapping(path="remove-records", methods="post")
     */
    public function removeChatRecords()
    {
        $user_id = $this->uid();

        //消息来源（1：好友消息 2：群聊消息）
        $source = $this->request->post('source', 0);

        //接收者ID（好友ID或者群聊ID）
        $receive_id = $this->request->post('receive_id', 0);

        //消息ID
        $record_ids = explode(',', $this->request->get('record_id', ''));
        if (!in_array($source, [1, 2]) || !check_int($receive_id) || !check_ids($record_ids)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->talkService->removeRecords($user_id, $source, $receive_id, $record_ids);

        return $isTrue ? $this->ajaxSuccess('删除成功...') : $this->ajaxError('删除失败...');
    }

    /**
     * @RequestMapping(path="forward-records", methods="post")
     */
    public function forwardChatRecords()
    {

    }

    /**
     * @RequestMapping(path="records", methods="get")
     */
    public function getChatRecords()
    {

    }

    /**
     * @RequestMapping(path="get-forward-records", methods="get")
     */
    public function getForwardRecords()
    {

    }

    /**
     * @RequestMapping(path="find-chat-records", methods="get")
     */
    public function findChatRecords()
    {

    }

    /**
     * @RequestMapping(path="search-chat-records", methods="get")
     */
    public function searchChatRecords()
    {

    }

    /**
     * @RequestMapping(path="get-records-context", methods="get")
     */
    public function getRecordsContext()
    {

    }

    /**
     * @RequestMapping(path="send-image", methods="post")
     */
    public function sendImage()
    {

    }

    /**
     * @RequestMapping(path="send-code-block", methods="post")
     */
    public function sendCodeBlock()
    {

    }

    /**
     * @RequestMapping(path="send-file", methods="post")
     */
    public function sendFile()
    {

    }

    /**
     * @RequestMapping(path="send-emoticon", methods="post")
     */
    public function sendEmoticon()
    {

    }
}
