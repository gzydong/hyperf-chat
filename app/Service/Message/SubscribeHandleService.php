<?php
declare(strict_types=1);

namespace App\Service\Message;

use App\Cache\GroupCache;
use App\Cache\SocketRoom;
use App\Constant\TalkEventConstant;
use App\Constant\TalkModeConstant;
use App\Model\Talk\TalkRecords;
use App\Model\User;
use App\Model\Contact\ContactApply;
use App\Repository\Contact\ContactRepository;
use App\Service\SocketClientService;

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
        TalkEventConstant::EVENT_TALK_KEYBOARD => 'onConsumeKeyboard',

        // 用户在线状态事件
        TalkEventConstant::EVENT_LOGIN         => 'onConsumeOnlineStatus',

        // 聊天消息推送事件
        TalkEventConstant::EVENT_TALK_REVOKE   => 'onConsumeRevokeTalk',

        // 好友申请相关事件
        TalkEventConstant::EVENT_CONTACT_APPLY => 'onConsumeFriendApply'
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
     * @param array $data 数据 ['uuid' => '','event' => '','data' => '','options' => ''];
     */
    public function handle(array $data)
    {
        if (!isset($data['uuid'], $data['event'], $data['data'], $data['options'])) {
            return;
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
            $fds[] = $this->clientService->findUserFds($sender_id);
            $fds[] = $this->clientService->findUserFds($receiver_id);
        } else if ($talk_type == TalkModeConstant::GROUP_CHAT) {
            foreach (SocketRoom::getInstance()->getRoomMembers(strval($receiver_id)) as $uid) {
                $fds[] = $this->clientService->findUserFds(intval($uid));
            }

            $groupInfo = GroupCache::getInstance()->getOrSetCache($receiver_id);
        }

        if (empty($fds)) return;

        $fds = array_unique(array_merge(...$fds));

        // 客户端ID去重
        if (!$fds) return;

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

        $message = di()->get(FormatMessageService::class)->handleChatRecords([$result->toArray()])[0];

        $this->push($fds, $this->toJson(TalkEventConstant::EVENT_TALK, [
            'sender_id'   => $sender_id,
            'receiver_id' => $receiver_id,
            'talk_type'   => $talk_type,
            'data'        => array_merge($message, [
                'group_name'   => $groupInfo ? $groupInfo['group_name'] : '',
                'group_avatar' => $groupInfo ? $groupInfo['avatar'] : ''
            ])
        ]));
    }

    /**
     * 键盘输入事件消息
     *
     * @param array $data 队列消息
     */
    public function onConsumeKeyboard(array $data): void
    {
        $fds = $this->clientService->findUserFds($data['data']['receiver_id']);

        $this->push($fds, $this->toJson(TalkEventConstant::EVENT_TALK_KEYBOARD, $data['data']));
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

        $ids = di()->get(ContactRepository::class)->findAllFriendIds($user_id);

        if (empty($ids)) return;

        $fds = [];
        foreach ($ids as $friend_id) {
            $fds[] = $this->clientService->findUserFds(intval($friend_id));
        }

        $fds = array_unique(array_merge(...$fds));

        $this->push($fds, $this->toJson(TalkEventConstant::EVENT_LOGIN, [
            'user_id' => $user_id,
            'status'  => $status
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
            $fds[] = $this->clientService->findUserFds($record->user_id);
            $fds[] = $this->clientService->findUserFds($record->receiver_id);
        } else if ($record->talk_type == TalkModeConstant::GROUP_CHAT) {
            foreach (SocketRoom::getInstance()->getRoomMembers(strval($record->receiver_id)) as $uid) {
                $fds[] = $this->clientService->findUserFds((int)$uid);
            }
        }

        if (empty($fds)) return;

        $fds = array_unique(array_merge(...$fds));

        if (!$fds) return;

        $this->push($fds, $this->toJson(TalkEventConstant::EVENT_TALK_REVOKE, [
            'talk_type'   => $record->talk_type,
            'sender_id'   => $record->user_id,
            'receiver_id' => $record->receiver_id,
            'record_id'   => $record->id,
        ]));
    }

    /**
     * 好友申请消息
     *
     * @param array $data 队列消息
     */
    public function onConsumeFriendApply(array $data): void
    {
        $data = $data['data'];

        $applyInfo = ContactApply::where('id', $data['apply_id'])->first();
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

        $this->push(array_unique($fds), $this->toJson(TalkEventConstant::EVENT_CONTACT_APPLY, $msg));
    }

    private function toJson(string $event, array $data): string
    {
        return json_encode(["event" => $event, "content" => $data]);
    }

    /**
     * WebSocket 消息推送
     *
     * @param array  $fds
     * @param string $message
     */
    private function push(array $fds, string $message): void
    {
        $server = server();
        foreach ($fds as $fd) {
            $server->exist(intval($fd)) && $server->push(intval($fd), $message);
        }
    }
}
