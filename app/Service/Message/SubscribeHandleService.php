<?php
declare(strict_types=1);

namespace App\Service\Message;

use App\Cache\SocketRoom;
use App\Constants\TalkEventConstant;
use App\Constants\TalkModeConstant;
use App\Model\Talk\TalkRecords;
use App\Model\Group\Group;
use App\Model\User;
use App\Model\UsersFriendApply;
use App\Service\SocketClientService;
use App\Service\UserService;

class SubscribeHandleService
{
    /**
     * 消息事件与回调事件绑定
     *
     * @var array
     */
    const EVENTS = [
        // 聊天消息事件
        TalkEventConstant::EVENT_TALK          => 'onConsumeTalk',

        // 键盘输入事件
        TalkEventConstant::EVENT_KEYBOARD      => 'onConsumeKeyboard',

        // 用户在线状态事件
        TalkEventConstant::EVENT_ONLINE_STATUS => 'onConsumeOnlineStatus',

        // 聊天消息推送事件
        TalkEventConstant::EVENT_REVOKE_TALK   => 'onConsumeRevokeTalk',

        // 好友申请相关事件
        TalkEventConstant::EVENT_FRIEND_APPLY  => 'onConsumeFriendApply'
    ];

    /**
     * @var SocketClientService
     */
    private $clientService;

    public function __construct(SocketClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * @param array $data
     * <pre>
     * [
     * 'uuid'    => '',
     * 'event'   => '',
     * 'data'    => '',
     * 'options' => ''
     * ];
     * </pre>
     */
    public function handle(array $data)
    {
        if (!isset($data['uuid'], $data['event'], $data['data'], $data['options'])) {
            return false;
        }

        if (isset(self::EVENTS[$data['event']])) {
            call_user_func([$this, self::EVENTS[$data['event']]], $data);
        }
    }

    /**
     * 对话聊天消息
     *
     * @param array $data 队列消息
     */
    public function onConsumeTalk(array $data): void
    {
        $talk_type   = $data['data']['talk_type'];
        $sender_id   = $data['data']['sender_id'];
        $receiver_id = $data['data']['receiver_id'];
        $record_id   = $data['data']['record_id'];

        $fds       = [];
        $groupInfo = null;

        if ($talk_type == TalkModeConstant::PRIVATE_CHAT) {
            $fds = array_merge(
                $this->clientService->findUserFds($sender_id),
                $this->clientService->findUserFds($receiver_id)
            );
        } else if ($talk_type == TalkModeConstant::GROUP_CHAT) {
            foreach (SocketRoom::getInstance()->getRoomMembers(strval($receiver_id)) as $uid) {
                $fds = array_merge($fds, $this->clientService->findUserFds(intval($uid)));
            }

            $groupInfo = Group::where('id', $receiver_id)->first(['group_name', 'avatar']);
        }

        // 客户端ID去重
        if (!$fds = array_unique($fds)) return;

        $result = TalkRecords::leftJoin('users', 'users.id', '=', 'talk_records.user_id')
            ->where('talk_records.id', $record_id)
            ->first([
                'talk_records.id',
                'talk_records.talk_type',
                'talk_records.msg_type',
                'talk_records.user_id',
                'talk_records.receiver_id',
                'talk_records.content',
                'talk_records.is_revoke',
                'talk_records.created_at',
                'users.nickname',
                'users.avatar',
            ]);

        if (!$result) return;


        $message = container()->get(FormatMessageService::class)->handleChatRecords([$result->toArray()])[0];
        $notify  = [
            'sender_id'   => $sender_id,
            'receiver_id' => $receiver_id,
            'talk_type'   => $talk_type,
            'data'        => array_merge($message, [
                'group_name'   => $groupInfo ? $groupInfo->group_name : '',
                'group_avatar' => $groupInfo ? $groupInfo->avatar : ''
            ])
        ];

        $this->socketPushNotify($fds, json_encode([TalkEventConstant::EVENT_TALK, $notify]));
    }

    /**
     * 键盘输入事件消息
     *
     * @param array $data 队列消息
     */
    public function onConsumeKeyboard(array $data): void
    {
        $fds = $this->clientService->findUserFds($data['data']['receiver_id']);

        $this->socketPushNotify($fds, json_encode([TalkEventConstant::EVENT_KEYBOARD, $data['data']]));
    }

    /**
     * 用户上线或下线消息
     *
     * @param array $data 队列消息
     */
    public function onConsumeOnlineStatus(array $data): void
    {
        $user_id = (int)$data['data']['user_id'];
        $status  = (int)$data['data']['status'];

        $fds = [];

        $ids = container()->get(UserService::class)->getFriendIds($user_id);
        foreach ($ids as $friend_id) {
            $fds = array_merge($fds, $this->clientService->findUserFds(intval($friend_id)));
        }

        $this->socketPushNotify(array_unique($fds), json_encode([
            TalkEventConstant::EVENT_ONLINE_STATUS, [
                'user_id' => $user_id,
                'status'  => $status
            ]
        ]));
    }

    /**
     * 撤销聊天消息
     *
     * @param array $data 队列消息
     */
    public function onConsumeRevokeTalk(array $data): void
    {
        $record = TalkRecords::where('id', $data['data']['record_id'])->first(['id', 'talk_type', 'user_id', 'receiver_id']);

        $fds = [];
        if ($record->talk_type == TalkModeConstant::PRIVATE_CHAT) {
            $fds = array_merge($fds, $this->clientService->findUserFds($record->user_id));
            $fds = array_merge($fds, $this->clientService->findUserFds($record->receiver_id));
        } else if ($record->talk_type == TalkModeConstant::GROUP_CHAT) {
            $userIds = SocketRoom::getInstance()->getRoomMembers(strval($record->receiver_id));
            foreach ($userIds as $uid) {
                $fds = array_merge($fds, $this->clientService->findUserFds((int)$uid));
            }
        }

        $fds = array_unique($fds);
        $this->socketPushNotify($fds, json_encode([TalkEventConstant::EVENT_REVOKE_TALK, [
            'talk_type'   => $record->talk_type,
            'sender_id'   => $record->user_id,
            'receiver_id' => $record->receiver_id,
            'record_id'   => $record->id,
        ]]));
    }

    /**
     * 好友申请消息
     *
     * @param array $data 队列消息
     */
    public function onConsumeFriendApply(array $data): void
    {
        $data = $data['data'];

        $applyInfo = UsersFriendApply::where('id', $data['apply_id'])->first();
        if (!$applyInfo) return;

        $fds = $this->clientService->findUserFds($data['type'] == 1 ? $applyInfo->friend_id : $applyInfo->user_id);

        if ($data['type'] == 1) {
            $msg = [
                'sender_id'   => $applyInfo->user_id,
                'receiver_id' => $applyInfo->friend_id,
                'remark'      => $applyInfo->remark,
            ];
        } else {
            $msg = [
                'sender_id'   => $applyInfo->friend_id,
                'receiver_id' => $applyInfo->user_id,
                'status'      => $applyInfo->status,
                'remark'      => $applyInfo->remark,
            ];
        }

        $friendInfo = User::select(['id', 'avatar', 'nickname', 'mobile', 'motto'])->find($data['type'] == 1 ? $applyInfo->user_id : $applyInfo->friend_id);

        $msg['friend'] = [
            'user_id'  => $friendInfo->id,
            'avatar'   => $friendInfo->avatar,
            'nickname' => $friendInfo->nickname,
            'mobile'   => $friendInfo->mobile,
        ];

        $this->socketPushNotify(array_unique($fds), json_encode([TalkEventConstant::EVENT_FRIEND_APPLY, $msg]));
    }

    /**
     * WebSocket 消息推送
     *
     * @param $fds
     * @param $message
     */
    private function socketPushNotify($fds, $message)
    {
        $server = server();
        foreach ($fds as $fd) {
            $server->exist(intval($fd)) && $server->push(intval($fd), $message);
        }
    }
}
