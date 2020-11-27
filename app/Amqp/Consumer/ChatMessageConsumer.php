<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Model\Chat\ChatRecordsForward;
use App\Model\UsersFriend;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Result;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Redis\Redis;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Builder\QueueBuilder;
use PhpAmqpLib\Message\AMQPMessage;
use App\Model\User;
use App\Helper\PushMessageHelper;
use App\Model\Chat\ChatRecord;
use App\Model\Chat\ChatRecordsCode;
use App\Model\Chat\ChatRecordsFile;
use App\Model\Chat\ChatRecordsInvite;
use App\Service\SocketFDService;
use App\Service\SocketRoomService;

/**
 * 消息推送消费者队列
 *
 * @Consumer(name="聊天消息消费者",enable=true)
 */
class ChatMessageConsumer extends ConsumerMessage
{
    /**
     * 交换机名称
     *
     * @var string
     */
    public $exchange = 'im.message.fanout';

    /**
     * 交换机类型
     *
     * @var string
     */
    public $type = Type::FANOUT;

    /**
     * 路由key
     *
     * @var string
     */
    public $routingKey = 'consumer:im:message';

    /**
     * @var SocketFDService
     */
    private $socketFDService;

    /**
     * @var SocketRoomService
     */
    private $socketRoomService;

    /**
     * 推送的消息类型推送绑定事件方法
     */
    const EVENTS = [
        // 聊天消息事件
        'event_talk' => 'onConsumeTalk',

        // 键盘输入事件
        'event_keyboard' => 'onConsumeKeyboard',

        // 用户在线状态事件
        'event_online_status' => 'onConsumeOnlineStatus',

        // 聊天消息推送事件
        'event_revoke_talk' => 'onConsumeRevokeTalk',

        // 好友申请相关事件
        'event_friend_apply' => 'onConsumeFriendApply'
    ];

    /**
     * ChatMessageConsumer constructor.
     * @param SocketFDService $socketFDService
     * @param SocketRoomService $socketRoomService
     */
    public function __construct(SocketFDService $socketFDService, SocketRoomService $socketRoomService)
    {
        $this->socketFDService = $socketFDService;
        $this->socketRoomService = $socketRoomService;
        $this->setQueue('queue:im-message:' . SERVER_RUN_ID);
    }

    /**
     * 重写创建队列生成类
     *
     * 注释：设置自动删除队列
     *
     * @return QueueBuilder
     */
    public function getQueueBuilder(): QueueBuilder
    {
        return parent::getQueueBuilder()->setAutoDelete(true);
    }

    /**
     * 消费队列消息
     *
     * @param $data
     * @param AMQPMessage $message
     * @return string
     */
    public function consumeMessage($data, AMQPMessage $message): string
    {
        if (isset($data['event'])) {
            return $this->{self::EVENTS[$data['event']]}($data, $message);
        }

        return Result::ACK;
    }

    /**
     * 对话聊天消息
     *
     * @param array $data 队列消息
     * @param AMQPMessage $message
     * @return string
     */
    public function onConsumeTalk(array $data, AMQPMessage $message)
    {
        $redis = container()->get(Redis::class);

        //[加锁]防止消息重复消费
        $lockName = sprintf('ws:message-lock:%s:%s', SERVER_RUN_ID, $data['uuid']);
        if (!$redis->rawCommand('SET', $lockName, 1, 'NX', 'EX', 60)) {
            return Result::ACK;
        }

        $source = $data['data']['source'];

        $fids = $this->socketFDService->findUserFds($data['data']['sender']);
        if ($source == 1) {// 私聊
            $fids = array_merge($fids, $this->socketFDService->findUserFds($data['data']['receive']));
        } else if ($source == 2) {//群聊
            $userIds = $this->socketRoomService->getRoomMembers(strval($data['data']['receive']));
            foreach ($userIds as $uid) {
                $fids = array_merge($fids, $this->socketFDService->findUserFds(intval($uid)));
            }
        }

        // 去重
        $fids = array_unique($fids);
        if (empty($fids)) {
            return Result::ACK;
        }

        /**
         * @var ChatRecord
         */
        $result = ChatRecord::leftJoin('users', 'users.id', '=', 'chat_records.user_id')
            ->where('chat_records.id', $data['data']['record_id'])
            ->first([
                'chat_records.id',
                'chat_records.source',
                'chat_records.msg_type',
                'chat_records.user_id',
                'chat_records.receive_id',
                'chat_records.content',
                'chat_records.is_revoke',
                'chat_records.created_at',

                'users.nickname',
                'users.avatar as avatar',
            ]);

        if (!$result) {
            return Result::ACK;
        }

        $file = [];
        $code_block = [];
        $forward = [];
        $invite = [];
        switch ($result->msg_type) {
            case 2://文件消息
                $file = ChatRecordsFile::where('record_id', $result->id)->first(['id', 'record_id', 'user_id', 'file_source', 'file_type', 'save_type', 'original_name', 'file_suffix', 'file_size', 'save_dir']);
                $file = $file ? $file->toArray() : [];
                if ($file) {
                    $file['file_url'] = get_media_url($file['save_dir']);
                }
                break;
            case 3://入群消息/退群消息
                $notifyInfo = ChatRecordsInvite::where('record_id', $result->id)->first([
                    'record_id', 'type', 'operate_user_id', 'user_ids'
                ]);

                $userInfo = User::where('id', $notifyInfo->operate_user_id)->first(['nickname', 'id']);
                $membersIds = explode(',', $notifyInfo->user_ids);

                $invite = [
                    'type' => $notifyInfo->type,
                    'operate_user' => ['id' => $userInfo->id, 'nickname' => $userInfo->nickname],
                    'users' => User::select('id', 'nickname')->whereIn('id', $membersIds)->get()->toArray()
                ];

                unset($notifyInfo, $userInfo, $membersIds);
                break;
            case 4://会话记录消息
                $forward = ['num' => 0, 'list' => []];

                $forwardInfo = ChatRecordsForward::where('record_id', $result->id)->first(['records_id', 'text']);
                if ($forwardInfo) {
                    $forward = [
                        'num' => substr_count($forwardInfo->records_id, ',') + 1,
                        'list' => json_decode($forwardInfo->text, true) ?? []
                    ];
                }

                break;
            case 5://代码块消息
                $code_block = ChatRecordsCode::where('record_id', $result->id)->first(['record_id', 'code_lang', 'code']);
                $code_block = $code_block ? $code_block->toArray() : [];
                break;
        }

        $msg = [
            'send_user' => $data['data']['sender'],
            'receive_user' => $data['data']['receive'],
            'source_type' => $data['data']['source'],
            'data' => PushMessageHelper::formatTalkMsg([
                'id' => $result->id,
                'msg_type' => $result->msg_type,
                'source' => $result->source,
                'avatar' => $result->avatar,
                'nickname' => $result->nickname,
                "user_id" => $result->user_id,
                "receive_id" => $result->receive_id,
                "created_at" => $result->created_at,
                "content" => $result->content,
                "file" => $file,
                "code_block" => $code_block,
                'forward' => $forward,
                'invite' => $invite
            ])
        ];

        $server = server();
        foreach ($fids as $fd) {
            $fd = intval($fd);
            if ($server->exist($fd)) {
                $server->push($fd, json_encode(['event_talk', $msg]));
            }
        }

        return Result::ACK;
    }

    /**
     * 键盘输入事件消息
     *
     * @param array $data 队列消息
     * @param AMQPMessage $message
     * @return string
     */
    public function onConsumeKeyboard(array $data, AMQPMessage $message)
    {
        $fds = $this->socketFDService->findUserFds($data['data']['receive_user']);
        $server = server();
        foreach ($fds as $fd) {
            $fd = intval($fd);
            if ($server->exist($fd)) {
                $server->push($fd, json_encode(['event_keyboard', $data['data']]));
            }
        }

        return Result::ACK;
    }

    /**
     * 用户上线或下线消息
     *
     * @param array $data 队列消息
     * @param AMQPMessage $message
     * @return string
     */
    public function onConsumeOnlineStatus(array $data, AMQPMessage $message)
    {
        $user_id = $data['data']['user_id'];
        $friends = UsersFriend::getFriendIds(intval($user_id));

        $fds = [];
        foreach ($friends as $friend_id) {
            $fds = array_merge($fds, $this->socketFDService->findUserFds(intval($friend_id)));
        }

        $fds = array_unique($fds);
        $server = server();
        foreach ($fds as $fd) {
            $fd = intval($fd);
            if ($server->exist($fd)) {
                $server->push($fd, json_encode(['event_online_status', $data['data']]));
            }
        }

        return Result::ACK;
    }

    /**
     * 撤销聊天消息
     *
     * @param array $data 队列消息
     * @param AMQPMessage $message
     * @return string
     */
    public function onConsumeRevokeTalk(array $data, AMQPMessage $message)
    {
        /** @var ChatRecord */
        $record = ChatRecord::where('id', $data['data']['record_id'])->first(['id', 'source', 'user_id', 'receive_id']);

        $fds = [];
        if ($record->source == 1) {
            $fds = array_merge($fds, $this->socketFDService->findUserFds($record->user_id));
            $fds = array_merge($fds, $this->socketFDService->findUserFds($record->receive_id));
        } else if ($record->source == 2) {
            $userIds = $this->socketRoomService->getRoomMembers(strval($record->receive_id));
            foreach ($userIds as $uid) {
                $fds = array_merge($fds, $this->socketFDService->findUserFds(intval($uid)));
            }
        }

        $fds = array_unique($fds);
        $server = server();
        foreach ($fds as $fd) {
            $fd = intval($fd);
            if ($server->exist($fd)) {
                $server->push($fd, json_encode(['event_revoke_talk', [
                    'record_id' => $record->id,
                    'source' => $record->source,
                    'user_id' => $record->user_id,
                    'receive_id' => $record->receive_id,
                ]]));
            }
        }

        return Result::ACK;
    }

    /**
     * 好友申请消息
     *
     * @param array $data
     * @param AMQPMessage $message
     */
    public function onConsumeFriendApply(array $data, AMQPMessage $message)
    {
        $fds = $this->socketFDService->findUserFds($data['data']['receive']);

        $fds = array_unique($fds);
        $server = server();
        foreach ($fds as $fd) {
            $fd = intval($fd);
            if ($server->exist($fd)) {
                $server->push($fd, json_encode(['event_friend_apply', $data['data']]));
            }
        }

        return Result::ACK;
    }
}
