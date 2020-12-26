<?php
declare(strict_types=1);
/**
 *
 * This is my open source code, please do not use it for commercial applications.
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author Yuandong<837215079@qq.com>
 * @link   https://github.com/gzydong/hyperf-chat
 */

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Result;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Redis\Redis;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Builder\QueueBuilder;
use PhpAmqpLib\Message\AMQPMessage;
use App\Model\User;
use App\Model\UsersFriend;
use App\Model\Chat\ChatRecord;
use App\Model\Chat\ChatRecordsCode;
use App\Model\Chat\ChatRecordsFile;
use App\Model\Chat\ChatRecordsInvite;
use App\Model\Chat\ChatRecordsForward;
use App\Service\SocketClientService;
use App\Service\SocketRoomService;
use App\Constants\SocketConstants;

/**
 * 消息推送消费者队列
 *
 * @Consumer(name="ConsumerChat",enable=true)
 */
class ChatMessageConsumer extends ConsumerMessage
{
    /**
     * 交换机名称
     *
     * @var string
     */
    public $exchange = SocketConstants::CONSUMER_MESSAGE_EXCHANGE;

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
     * @var SocketClientService
     */
    private $socketClientService;

    /**
     * @var SocketRoomService
     */
    private $socketRoomService;

    /**
     * 消息事件与回调事件绑定
     *
     * @var array
     */
    const EVENTS = [
        // 聊天消息事件
        SocketConstants::EVENT_TALK => 'onConsumeTalk',

        // 键盘输入事件
        SocketConstants::EVENT_KEYBOARD => 'onConsumeKeyboard',

        // 用户在线状态事件
        SocketConstants::EVENT_ONLINE_STATUS => 'onConsumeOnlineStatus',

        // 聊天消息推送事件
        SocketConstants::EVENT_REVOKE_TALK => 'onConsumeRevokeTalk',

        // 好友申请相关事件
        SocketConstants::EVENT_FRIEND_APPLY => 'onConsumeFriendApply'
    ];

    /**
     * ChatMessageConsumer constructor.
     * @param SocketClientService $socketClientService
     * @param SocketRoomService $socketRoomService
     */
    public function __construct(SocketClientService $socketClientService, SocketRoomService $socketRoomService)
    {
        $this->socketClientService = $socketClientService;
        $this->socketRoomService = $socketRoomService;

        // 动态设置 Rabbit MQ 消费队列名称
        $this->setQueue('queue:im_message:' . SERVER_RUN_ID);
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
            $redis = container()->get(Redis::class);

            //[加锁]防止消息重复消费
            $lockName = sprintf('ws:message-lock:%s:%s', SERVER_RUN_ID, $data['uuid']);
            if (!$redis->rawCommand('SET', $lockName, 1, 'NX', 'EX', 60)) {
                return Result::ACK;
            }

            // 调用对应事件绑定的回调方法
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
    public function onConsumeTalk(array $data, AMQPMessage $message): string
    {
        $source = $data['data']['source'];

        $fds = $this->socketClientService->findUserFds($data['data']['sender']);
        if ($source == 1) {// 私聊
            $fds = array_merge($fds, $this->socketClientService->findUserFds($data['data']['receive']));
        } else if ($source == 2) {//群聊
            $userIds = $this->socketRoomService->getRoomMembers(strval($data['data']['receive']));
            foreach ($userIds as $uid) {
                $fds = array_merge($fds, $this->socketClientService->findUserFds((int)$uid));
            }
        }

        // 客户端ID去重
        if (!$fds = array_unique($fds)) {
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

        if (!$result) return Result::ACK;

        $file = [];
        $code_block = [];
        $forward = [];
        $invite = [];
        switch ($result->msg_type) {
            case 2://文件消息
                $file = ChatRecordsFile::where('record_id', $result->id)->first(['id', 'record_id', 'user_id', 'file_source', 'file_type', 'save_type', 'original_name', 'file_suffix', 'file_size', 'save_dir']);
                $file = $file ? $file->toArray() : [];
                $file && $file['file_url'] = get_media_url($file['save_dir']);

                break;
            case 3://入群消息/退群消息
                $notifyInfo = ChatRecordsInvite::where('record_id', $result->id)->first([
                    'record_id', 'type', 'operate_user_id', 'user_ids'
                ]);

                $userInfo = User::where('id', $notifyInfo->operate_user_id)->first(['nickname', 'id']);

                $invite = [
                    'type' => $notifyInfo->type,
                    'operate_user' => ['id' => $userInfo->id, 'nickname' => $userInfo->nickname],
                    'users' => User::whereIn('id', parse_ids($notifyInfo->user_ids))->get(['id', 'nickname'])->toArray()
                ];

                unset($notifyInfo, $userInfo);
                break;
            case 4://会话记录消息
                $forward = ['num' => 0, 'list' => []];

                $forwardInfo = ChatRecordsForward::where('record_id', $result->id)->first(['records_id', 'text']);
                if ($forwardInfo) {
                    $forward = [
                        'num' => count(parse_ids($forwardInfo->records_id)),
                        'list' => json_decode($forwardInfo->text, true) ?? []
                    ];
                }

                break;
            case 5://代码块消息
                $code_block = ChatRecordsCode::where('record_id', $result->id)->first(['record_id', 'code_lang', 'code']);
                $code_block = $code_block ? $code_block->toArray() : [];
                break;
        }

        $notify = [
            'send_user' => $data['data']['sender'],
            'receive_user' => $data['data']['receive'],
            'source_type' => $data['data']['source'],
            'data' => $this->formatTalkMessage([
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

        unset($result, $file, $code_block, $forward, $invite);

        $this->socketPushNotify($fds, json_encode([SocketConstants::EVENT_TALK, $notify]));

        return Result::ACK;
    }

    /**
     * 键盘输入事件消息
     *
     * @param array $data 队列消息
     * @param AMQPMessage $message
     * @return string
     */
    public function onConsumeKeyboard(array $data, AMQPMessage $message): string
    {
        $fds = $this->socketClientService->findUserFds($data['data']['receive_user']);

        $this->socketPushNotify($fds, json_encode([SocketConstants::EVENT_KEYBOARD, $data['data']]));

        return Result::ACK;
    }

    /**
     * 用户上线或下线消息
     *
     * @param array $data 队列消息
     * @param AMQPMessage $message
     * @return string
     */
    public function onConsumeOnlineStatus(array $data, AMQPMessage $message): string
    {
        $friends = UsersFriend::getFriendIds((int)$data['data']['user_id']);

        $fds = [];
        foreach ($friends as $friend_id) {
            $fds = array_merge($fds, $this->socketClientService->findUserFds((int)$friend_id));
        }

        $this->socketPushNotify(array_unique($fds), json_encode([SocketConstants::EVENT_ONLINE_STATUS, $data['data']]));

        return Result::ACK;
    }

    /**
     * 撤销聊天消息
     *
     * @param array $data 队列消息
     * @param AMQPMessage $message
     * @return string
     */
    public function onConsumeRevokeTalk(array $data, AMQPMessage $message): string
    {
        /** @var ChatRecord */
        $record = ChatRecord::where('id', $data['data']['record_id'])->first(['id', 'source', 'user_id', 'receive_id']);

        $fds = [];
        if ($record->source == 1) {
            $fds = array_merge($fds, $this->socketClientService->findUserFds((int)$record->user_id));
            $fds = array_merge($fds, $this->socketClientService->findUserFds((int)$record->receive_id));
        } else if ($record->source == 2) {
            $userIds = $this->socketRoomService->getRoomMembers(strval($record->receive_id));
            foreach ($userIds as $uid) {
                $fds = array_merge($fds, $this->socketClientService->findUserFds((int)$uid));
            }
        }

        $this->socketPushNotify(
            array_unique($fds),
            json_encode([SocketConstants::EVENT_REVOKE_TALK, [
                'record_id' => $record->id,
                'source' => $record->source,
                'user_id' => $record->user_id,
                'receive_id' => $record->receive_id,
            ]])
        );

        return Result::ACK;
    }

    /**
     * 好友申请消息
     *
     * @param array $data 队列消息
     * @param AMQPMessage $message
     * @return string
     */
    public function onConsumeFriendApply(array $data, AMQPMessage $message): string
    {
        $fds = $this->socketClientService->findUserFds($data['data']['receive']);

        $this->socketPushNotify(array_unique($fds), json_encode([SocketConstants::EVENT_FRIEND_APPLY, $data['data']]));

        return Result::ACK;
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
            $server->exist($fd) && $server->push($fd, $message);
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
            "id" => 0,//消息记录ID
            "source" => 1,//消息来源[1:好友私信;2:群聊]
            "msg_type" => 1,
            "user_id" => 0,//发送者用户ID
            "receive_id" => 0,//接收者ID[好友ID或群ID]
            "content" => '',//文本消息
            "is_revoke" => 0,//消息是否撤销

            // 发送消息人的信息
            "nickname" => "",
            "avatar" => "",

            // 不同的消息类型
            "file" => [],
            "code_block" => [],
            "forward" => [],
            "invite" => [],

            // 消息创建时间
            "created_at" => "",
        ];

        return array_merge($message, array_intersect_key($data, $message));
    }
}
