<?php

namespace App\Service\Message;

use App\Cache\SocketRoom;
use App\Constants\TalkMessageEvent;
use App\Constants\TalkMessageType;
use App\Constants\TalkMode;
use App\Model\Talk\TalkRecords;
use App\Model\Talk\TalkRecordsCode;
use App\Model\Talk\TalkRecordsFile;
use App\Model\Talk\TalkRecordsForward;
use App\Model\Talk\TalkRecordsInvite;
use App\Model\Group\Group;
use App\Model\User;
use App\Model\UsersFriendsApply;
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
        TalkMessageEvent::EVENT_TALK          => 'onConsumeTalk',

        // 键盘输入事件
        TalkMessageEvent::EVENT_KEYBOARD      => 'onConsumeKeyboard',

        // 用户在线状态事件
        TalkMessageEvent::EVENT_ONLINE_STATUS => 'onConsumeOnlineStatus',

        // 聊天消息推送事件
        TalkMessageEvent::EVENT_REVOKE_TALK   => 'onConsumeRevokeTalk',

        // 好友申请相关事件
        TalkMessageEvent::EVENT_FRIEND_APPLY  => 'onConsumeFriendApply'
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
     * @return string
     */
    public function onConsumeTalk(array $data): string
    {
        $talk_type   = $data['data']['talk_type'];
        $sender_id   = $data['data']['sender_id'];
        $receiver_id = $data['data']['receiver_id'];
        $record_id   = $data['data']['record_id'];

        $fds       = [];
        $groupInfo = null;

        if ($talk_type == TalkMode::PRIVATE_CHAT) {
            $fds = array_merge(
                $this->clientService->findUserFds($sender_id),
                $this->clientService->findUserFds($receiver_id)
            );
        } else if ($talk_type == TalkMode::GROUP_CHAT) {
            foreach (SocketRoom::getInstance()->getRoomMembers(strval($receiver_id)) as $uid) {
                $fds = array_merge($fds, $this->clientService->findUserFds(intval($uid)));
            }

            $groupInfo = Group::where('id', $receiver_id)->first(['group_name', 'avatar']);
        }

        // 客户端ID去重
        if (!$fds = array_unique($fds)) {
            return true;
        }

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

        if (!$result) return true;

        $file = $code_block = $forward = $invite = [];

        switch ($result->msg_type) {
            case TalkMessageType::FILE_MESSAGE:
                $file = TalkRecordsFile::where('record_id', $result->id)->first([
                    'id', 'record_id', 'user_id', 'file_source', 'file_type',
                    'save_type', 'original_name', 'file_suffix', 'file_size', 'save_dir'
                ]);

                $file = $file ? $file->toArray() : [];
                $file && $file['file_url'] = get_media_url($file['save_dir']);
                break;

            case TalkMessageType::FORWARD_MESSAGE:
                $forward     = ['num' => 0, 'list' => []];
                $forwardInfo = TalkRecordsForward::where('record_id', $result->id)->first(['records_id', 'text']);
                if ($forwardInfo) {
                    $forward = [
                        'num'  => count(parse_ids($forwardInfo->records_id)),
                        'list' => json_decode($forwardInfo->text, true) ?? []
                    ];
                }
                break;

            case TalkMessageType::CODE_MESSAGE:
                $code_block = TalkRecordsCode::where('record_id', $result->id)->first(['record_id', 'code_lang', 'code']);
                $code_block = $code_block ? $code_block->toArray() : [];
                break;

            case TalkMessageType::GROUP_INVITE_MESSAGE:
                $notifyInfo = TalkRecordsInvite::where('record_id', $result->id)->first([
                    'record_id', 'type', 'operate_user_id', 'user_ids'
                ]);

                $userInfo = User::where('id', $notifyInfo->operate_user_id)->first(['nickname', 'id']);
                $invite   = [
                    'type'         => $notifyInfo->type,
                    'operate_user' => ['id' => $userInfo->id, 'nickname' => $userInfo->nickname],
                    'users'        => User::whereIn('id', parse_ids($notifyInfo->user_ids))->get(['id', 'nickname'])->toArray()
                ];

                unset($notifyInfo, $userInfo);
                break;
        }

        $notify = [
            'sender_id'   => $sender_id,
            'receiver_id' => $receiver_id,
            'talk_type'   => $talk_type,
            'data'        => $this->formatTalkMessage([
                'id'           => $result->id,
                'talk_type'    => $result->talk_type,
                'msg_type'     => $result->msg_type,
                "user_id"      => $result->user_id,
                "receiver_id"  => $result->receiver_id,
                'avatar'       => $result->avatar,
                'nickname'     => $result->nickname,
                'group_name'   => $groupInfo ? $groupInfo->group_name : '',
                'group_avatar' => $groupInfo ? $groupInfo->avatar : '',
                "created_at"   => $result->created_at,
                "content"      => $result->content,
                "file"         => $file,
                "code_block"   => $code_block,
                'forward'      => $forward,
                'invite'       => $invite
            ])
        ];

        $this->socketPushNotify($fds, json_encode([TalkMessageEvent::EVENT_TALK, $notify]));

        return true;
    }

    /**
     * 键盘输入事件消息
     *
     * @param array $data 队列消息
     * @return string
     */
    public function onConsumeKeyboard(array $data): string
    {
        $fds = $this->clientService->findUserFds($data['data']['receiver_id']);

        $this->socketPushNotify($fds, json_encode([TalkMessageEvent::EVENT_KEYBOARD, $data['data']]));

        return true;
    }

    /**
     * 用户上线或下线消息
     *
     * @param array $data 队列消息
     * @return string
     */
    public function onConsumeOnlineStatus(array $data): string
    {
        $user_id = (int)$data['data']['user_id'];
        $status  = (int)$data['data']['status'];

        $fds = [];

        $ids = container()->get(UserService::class)->getFriendIds($user_id);
        foreach ($ids as $friend_id) {
            $fds = array_merge($fds, $this->clientService->findUserFds(intval($friend_id)));
        }

        $this->socketPushNotify(array_unique($fds), json_encode([
            TalkMessageEvent::EVENT_ONLINE_STATUS, [
                'user_id' => $user_id,
                'status'  => $status
            ]
        ]));

        return true;
    }

    /**
     * 撤销聊天消息
     *
     * @param array $data 队列消息
     * @return string
     */
    public function onConsumeRevokeTalk(array $data): string
    {
        $record = TalkRecords::where('id', $data['data']['record_id'])->first(['id', 'talk_type', 'user_id', 'receiver_id']);

        $fds = [];
        if ($record->talk_type == TalkMode::PRIVATE_CHAT) {
            $fds = array_merge($fds, $this->clientService->findUserFds($record->user_id));
            $fds = array_merge($fds, $this->clientService->findUserFds($record->receiver_id));
        } else if ($record->talk_type == TalkMode::GROUP_CHAT) {
            $userIds = SocketRoom::getInstance()->getRoomMembers(strval($record->receiver_id));
            foreach ($userIds as $uid) {
                $fds = array_merge($fds, $this->clientService->findUserFds((int)$uid));
            }
        }

        $fds = array_unique($fds);
        $this->socketPushNotify($fds, json_encode([TalkMessageEvent::EVENT_REVOKE_TALK, [
            'talk_type'   => $record->talk_type,
            'sender_id'   => $record->user_id,
            'receiver_id' => $record->receiver_id,
            'record_id'   => $record->id,
        ]]));

        return true;
    }

    /**
     * 好友申请消息
     *
     * @param array $data 队列消息
     * @return string
     */
    public function onConsumeFriendApply(array $data): string
    {
        $data = $data['data'];

        $applyInfo = UsersFriendsApply::where('id', $data['apply_id'])->first();
        if (!$applyInfo) return true;

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

        $this->socketPushNotify(array_unique($fds), json_encode([TalkMessageEvent::EVENT_FRIEND_APPLY, $msg]));

        return true;
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

    /**
     * 格式化对话的消息体
     *
     * @param array $data 对话的消息
     * @return array
     */
    private function formatTalkMessage(array $data): array
    {
        $message = [
            "id"           => 0, // 消息记录ID
            "talk_type"    => 1, // 消息来源[1:好友私信;2:群聊]
            "msg_type"     => 1, // 消息类型
            "user_id"      => 0, // 发送者用户ID
            "receiver_id"  => 0, // 接收者ID[好友ID或群ID]

            // 发送消息人的信息
            "nickname"     => "",// 用户昵称
            "avatar"       => "",// 用户头像
            "group_name"   => "",// 群组名称
            "group_avatar" => "",// 群组头像

            // 不同的消息类型
            "file"         => [],
            "code_block"   => [],
            "forward"      => [],
            "invite"       => [],

            // 消息创建时间
            "content"      => '',// 文本消息
            "created_at"   => "",

            // 消息属性
            "is_revoke"    => 0, // 消息是否撤销
        ];

        return array_merge($message, array_intersect_key($data, $message));
    }
}
