<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Helper\PushMessageHelper;
use App\Model\Chat\ChatRecord;
use App\Model\Chat\ChatRecordsCode;
use App\Model\Chat\ChatRecordsFile;
use App\Service\SocketFDService;
use App\Service\SocketRoomService;
use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Redis\Redis;
use PhpAmqpLib\Message\AMQPMessage;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Builder\QueueBuilder;

/**
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
     * ChatMessageConsumer constructor.
     * @param SocketFDService $socketFDService
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
        $redis = container()->get(Redis::class);

        //[加锁]防止消息重复消费
        $lockName = sprintf('ws:message-lock:%s:%s', SERVER_RUN_ID, $data['uuid']);
        if (!$redis->rawCommand('SET', $lockName, 1, 'NX', 'EX', 60)) {
            return Result::ACK;
        }

        $source = $data['source'];

        $fids = $this->socketFDService->findUserFds($data['sender']);
        if ($source == 1) {// 私聊
            $fids = array_merge($fids, $this->socketFDService->findUserFds($data['receive']));
        } else if ($source == 2) {//群聊
            $userIds = $this->socketRoomService->getRoomMembers(strval($data['receive']));
            foreach ($userIds as $uid) {
                $fids = array_merge($fids, $this->socketFDService->findUserFds(intval($uid)));
            }
        }

        // 去重
        $fids = array_unique($fids);
        if (empty($fids)) {
            return Result::ACK;
        }

        $result = ChatRecord::leftJoin('users', 'users.id', '=', 'chat_records.user_id')
            ->where('chat_records.id', $data['record_id'])
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

        $file = [];
        $code_block = [];
        if ($result->msg_type == 2) {
            $file = ChatRecordsFile::where('record_id', $result->id)->first(['id', 'record_id', 'user_id', 'file_source', 'file_type', 'save_type', 'original_name', 'file_suffix', 'file_size', 'save_dir']);
            $file = $file ? $file->toArray() : [];
            if ($file) {
                $file['file_url'] = get_media_url($file['save_dir']);
            }
        } else if ($result->msg_type == 5) {
            $code_block = ChatRecordsCode::where('record_id', $result->id)->first(['record_id', 'code_lang', 'code']);
            $code_block = $code_block ? $code_block->toArray() : [];
        }

        $msg = [
            'send_user' => $data['sender'],
            'receive_user' => $data['receive'],
            'source_type' => $data['source'],
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
                "code_block" => $code_block
            ])
        ];

        $server = server();
        foreach ($fids as $fd) {
            $fd = intval($fd);
            if ($server->exist($fd)) {
                $server->push($fd, json_encode(['chat_message', $msg]));
            }
        }

        unset($fids, $result, $msg);
        return Result::ACK;
    }
}
