<?php

namespace App\Controller\Api\V1;

use App\Cache\UnreadTalkCache;
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
     * @RequestMapping(path="create", methods="post")
     */
    public function create()
    {
        $uid = $this->uid();
        $type = $this->request->post('type', 1);//创建的类型
        $receive_id = $this->request->post('receive_id', 0);//接收者ID

        if (!in_array($type, [1, 2]) || !check_int($receive_id)) {
            return $this->ajaxParamError();
        }

        if ($type == 1) {
            if (!UserFriends::isFriend($uid, $receive_id)) {
                return $this->ajaxReturn(305, '暂不属于好友关系，无法进行聊天...');
            }
        } else {
            if (!UserGroup::isMember($receive_id, $uid)) {
                return $this->ajaxReturn(305, '暂不属于群成员，无法进行群聊 ...');
            }
        }

        $result = UserChatList::addItem($uid, $receive_id, $type);
        if (!$result) {
            return $this->ajaxError('创建失败...');
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
            $data['unread_num'] = app('unread.talk')->get($uid, $result['friend_id']);

            $userInfo = User::where('id', $uid)->first(['nickname', 'avatar']);
            $data['name'] = $userInfo->nickname;
            $data['avatar'] = $userInfo->avatar;
        } else if ($result['type'] == 2) {
            $groupInfo = UserGroup::where('id', $result['group_id'])->first(['group_name', 'avatar']);
            $data['name'] = $groupInfo->group_name;
            $data['avatar'] = $groupInfo->avatar;
        }

        $records = LastMsgCache::get($result['type'] == 1 ? $result['friend_id'] : $result['group_id'], $result['type'] == 1 ? $uid : 0);
        if ($records) {
            $data['msg_text'] = $records['text'];
            $data['updated_at'] = $records['created_at'];
        }

        return $this->ajaxSuccess('创建成功...', ['talkItem' => $data]);
    }

    /**
     * @RequestMapping(path="delete", methods="post")
     */
    public function delete()
    {

    }

    /**
     * @RequestMapping(path="topping", methods="post")
     */
    public function topping()
    {

    }

    /**
     * @RequestMapping(path="set-not-disturb", methods="post")
     */
    public function setNotDisturb()
    {

    }

    /**
     * @RequestMapping(path="update-unread-num", methods="post")
     */
    public function updateUnreadNum()
    {

    }

    /**
     * @RequestMapping(path="revoke-records", methods="post")
     */
    public function revokeChatRecords()
    {

    }

    /**
     * @RequestMapping(path="remove-records", methods="post")
     */
    public function removeChatRecords()
    {

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
