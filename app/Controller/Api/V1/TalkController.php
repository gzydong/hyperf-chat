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
        if ($result = $this->unreadTalkCache->getAll($user_id)) {
            $this->talkService->updateUnreadTalkList($user_id, $result);
        }

        // 获取聊天列表
        if ($rows = $this->talkService->talks($user_id)) {
            $rows = arraysSort($rows, 'updated_at');
        }

        return $this->response->success($rows);
    }

    /**
     * 新增对话列表
     *
     * @RequestMapping(path="create", methods="post")
     */
    public function create()
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
            $data['unread_num'] = $this->unreadTalkCache->get($user_id, $result['friend_id']);
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
        $params = $this->request->inputs(['receive_id', 'type', 'not_disturb']);
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
     */
    public function updateUnreadNum()
    {
        $params = $this->request->inputs(['receive', 'type']);
        $this->validate($params, [
            'receive' => 'required|integer|min:0',
            'type' => 'required|integer|min:0'
        ]);

        // 设置好友消息未读数
        if ($params['type'] == 1) {
            $this->unreadTalkCache->del($this->uid(), $params['receive']);
        }

        return $this->response->success([], 'success');
    }

    /**
     * 撤回聊天对话消息
     *
     * @RequestMapping(path="revoke-records", methods="post")
     */
    public function revokeChatRecords()
    {
        $params = $this->request->inputs(['record_id']);
        $this->validate($params, [
            'record_id' => 'required|integer|min:0'
        ]);

        [$isTrue, $message,] = $this->talkService->revokeRecord($this->uid(), $params['record_id']);

        return $isTrue
            ? $this->response->success([], $message)
            : $this->response->fail($message);
    }

    /**
     * 删除聊天记录
     *
     * @RequestMapping(path="remove-records", methods="post")
     */
    public function removeChatRecords()
    {
        $params = $this->request->inputs(['source', 'record_id', 'receive_id']);
        $this->validate($params, [
            'source' => 'required|in:1,2',//消息来源（1：好友消息 2：群聊消息）
            'record_id' => 'required|integer|min:0',
            'receive_id' => 'required|integer|min:0'
        ]);

        $record_ids = explode(',', $params['record_id']);

        $isTrue = $this->talkService->removeRecords(
            $this->uid(),
            $params['source'],
            $params['receive_id'],
            $record_ids
        );

        return $isTrue
            ? $this->response->success([], '删除成功...')
            : $this->response->fail('删除失败...');
    }

    /**
     * 转发聊天记录(待优化)
     *
     * @RequestMapping(path="forward-records", methods="post")
     */
    public function forwardChatRecords()
    {
        $params = $this->request->inputs(['source', 'records_ids', 'receive_id', 'forward_mode', 'receive_user_ids', 'receive_group_ids']);
        $this->validate($params, [
            //消息来源[1：好友消息 2：群聊消息]
            'source' => 'required|in:1,2',
            //聊天记录ID，多个逗号拼接
            'records_ids' => 'required',
            //接收者ID（好友ID或者群聊ID）
            'receive_id' => 'required|integer|min:0',
            //转发方方式[1:逐条转发;2:合并转发]
            'forward_mode' => 'required|in:1,2',
            //转发的好友的ID
            'receive_user_ids' => 'required|array',
            //转发的群聊ID
            'receive_group_ids' => 'required|array',
        ]);

        $user_id = $this->uid();
        $items = array_merge(
            array_map(function ($friend_id) {
                return ['source' => 1, 'id' => $friend_id];
            }, $params['receive_user_ids']),
            array_map(function ($group_id) {
                return ['source' => 2, 'id' => $group_id];
            }, $params['receive_group_ids'])
        );

        if ($params['forward_mode'] == 1) {//单条转发
            $ids = $this->talkService->forwardRecords($user_id, $params['receive_id'], $params['records_ids']);
        } else {//合并转发
            $ids = $this->talkService->mergeForwardRecords($user_id, $params['receive_id'], $params['source'], $params['records_ids'], $items);
        }

        if (!$ids) {
            return $this->response->fail('转发失败...');
        }

        if ($params['receive_user_ids']) {
            foreach ($params['receive_user_ids'] as $v) {
                $this->unreadTalkCache->setInc($v, $user_id);
            }
        }

        //这里需要调用WebSocket推送接口
        // ...

        return $this->response->success([], '转发成功...');
    }

    /**
     * 获取对话面板中的聊天记录
     *
     * @RequestMapping(path="records", methods="get")
     */
    public function getChatRecords()
    {
        $params = $this->request->inputs(['record_id', 'source', 'receive_id']);
        $this->validate($params, [
            'source' => 'required|in:1,2',//消息来源（1：好友消息 2：群聊消息）
            'record_id' => 'required|integer|min:0',
            'receive_id' => 'required|integer|min:1',
        ]);

        $user_id = $this->uid();
        $limit = 30;

        // 判断是否属于群成员
        if ($params['source'] == 2 && UsersGroup::isMember($params['receive_id'], $user_id) == false) {
            return $this->response->success([
                'rows' => [],
                'record_id' => 0,
                'limit' => $limit
            ], '非群聊成员不能查看群聊信息...');
        }

        $result = $this->talkService->getChatRecords(
            $user_id,
            $params['receive_id'],
            $params['source'],
            $params['record_id'],
            $limit
        );

        return $this->response->success([
            'rows' => $result,
            'record_id' => $result ? $result[count($result) - 1]['id'] : 0,
            'limit' => $limit
        ]);
    }

    /**
     * 获取转发记录详情
     *
     * @RequestMapping(path="get-forward-records", methods="get")
     */
    public function getForwardRecords()
    {
        $params = $this->request->inputs(['records_id']);
        $this->validate($params, [
            'records_id' => 'required|integer|min:0'
        ]);

        $rows = $this->talkService->getForwardRecords(
            $this->uid(),
            $params['records_id']
        );

        return $this->response->success(['rows' => $rows]);
    }

    /**
     * 查询聊天记录
     *
     * @RequestMapping(path="find-chat-records", methods="get")
     */
    public function findChatRecords()
    {
        $params = $this->request->inputs(['record_id', 'source', 'receive_id', 'msg_type']);
        $this->validate($params, [
            'source' => 'required|in:1,2',//消息来源（1：好友消息 2：群聊消息）
            'record_id' => 'required|integer|min:0',
            'receive_id' => 'required|integer|min:1',
            'msg_type' => 'required|in:0,1,2,3,4,5,6',
        ]);

        $user_id = $this->uid();
        $limit = 30;

        // 判断是否属于群成员
        if ($params['source'] == 2 && UsersGroup::isMember($params['receive_id'], $user_id) == false) {
            return $this->response->success([
                'rows' => [],
                'record_id' => 0,
                'limit' => $limit
            ], '非群聊成员不能查看群聊信息...');
        }

        if (in_array($params['msg_type'], [1, 2, 4, 5])) {
            $msg_type = [$params['msg_type']];
        } else {
            $msg_type = [1, 2, 4, 5];
        }

        $result = $this->talkService->getChatRecords(
            $user_id,
            $params['receive_id'],
            $params['source'],
            $params['record_id'],
            $limit,
            $msg_type
        );

        return $this->response->success([
            'rows' => $result,
            'record_id' => $result ? $result[count($result) - 1]['id'] : 0,
            'limit' => $limit
        ]);
    }

    /**
     * 搜索聊天记录（待开发）
     *
     * @RequestMapping(path="search-chat-records", methods="get")
     */
    public function searchChatRecords()
    {

    }

    /**
     * 获取聊天记录上下文数据（待开发）
     *
     * @RequestMapping(path="get-records-context", methods="get")
     */
    public function getRecordsContext()
    {

    }

    /**
     * 上传聊天对话图片（待优化）
     *
     * @RequestMapping(path="send-image", methods="post")
     */
    public function sendImage()
    {

    }

    /**
     * 发送代码块消息
     *
     * @RequestMapping(path="send-code-block", methods="post")
     */
    public function sendCodeBlock()
    {

    }

    /**
     * 发送文件消息
     *
     * @RequestMapping(path="send-file", methods="post")
     */
    public function sendFile()
    {

    }

    /**
     * 发送表情包消息
     *
     * @RequestMapping(path="send-emoticon", methods="post")
     */
    public function sendEmoticon()
    {

    }
}