<?php
declare(strict_types=1);

namespace App\Service;

use App\Constant\TalkEventConstant;
use App\Constant\TalkMessageType;
use App\Constant\TalkModeConstant;
use App\Event\TalkEvent;
use App\Model\Robot;
use App\Service\Group\GroupMemberService;
use App\Service\Message\FormatMessageService;
use App\Model\Talk\TalkRecords;
use App\Model\Talk\TalkRecordsForward;
use App\Traits\PagingTrait;
use Hyperf\DbConnection\Db;

class TalkService extends BaseService
{
    use PagingTrait;

    /**
     * 查询对话页面的历史聊天记录
     *
     * @param int   $user_id     用户ID
     * @param int   $receiver_id 接收者ID（好友ID或群ID）
     * @param int   $talk_type   对话类型[1:好友消息;2:群聊消息;]
     * @param int   $record_id   上一次查询的聊天记录ID
     * @param int   $limit       查询数据长度
     * @param array $msg_type    消息类型
     * @return array
     */
    public function getChatRecords(int $user_id, int $receiver_id, int $talk_type, int $record_id, $limit = 30, $msg_type = []): array
    {
        $fields = [
            'talk_records.id',
            'talk_records.talk_type',
            'talk_records.msg_type',
            'talk_records.user_id',
            'talk_records.receiver_id',
            'talk_records.is_revoke',
            'talk_records.content',
            'talk_records.created_at',
            'users.nickname',
            'users.avatar as avatar',
        ];

        $model = TalkRecords::select($fields);
        $model->leftJoin('users', 'users.id', '=', 'talk_records.user_id');
        if ($record_id) {
            $model->where('talk_records.id', '<', $record_id);
        }

        if ($talk_type == TalkModeConstant::PRIVATE_CHAT) {
            $model->where(function ($query) use ($user_id, $receiver_id) {
                $query->where([
                    ['talk_records.user_id', '=', $user_id],
                    ['talk_records.receiver_id', '=', $receiver_id]
                ])->orWhere([
                    ['talk_records.user_id', '=', $receiver_id],
                    ['talk_records.receiver_id', '=', $user_id]
                ]);
            });
        } else {
            $model->where('talk_records.receiver_id', $receiver_id);
        }

        $model->where('talk_records.talk_type', $talk_type);

        if ($msg_type) {
            $model->whereIn('talk_records.msg_type', $msg_type);
        }

        // 过滤用户删除记录
        $model->whereNotExists(function ($query) use ($user_id) {
            $prefix = config('databases.default.prefix');
            $query->select(Db::raw(1))->from('talk_records_delete');
            $query->whereRaw("{$prefix}talk_records_delete.record_id = {$prefix}talk_records.id and {$prefix}talk_records_delete.user_id = {$user_id}");
            $query->limit(1);
        });

        $rows = $model->orderBy('talk_records.id', 'desc')->limit($limit)->get()->toArray();

        if ($record_id === 0 && $talk_type == TalkModeConstant::PRIVATE_CHAT && empty($msg_type)) {
            $isBoot = Robot::where('user_id', $receiver_id)->exists();
            if (!$isBoot && !di()->get(UserFriendService::class)->isFriend($user_id, $receiver_id, true)) {
                array_unshift($rows, [
                    'id'          => ($rows[0]['id'] ?? 0) + 1,
                    'talk_type'   => TalkModeConstant::PRIVATE_CHAT,
                    'msg_type'    => TalkMessageType::SYSTEM_TEXT_MESSAGE,
                    'user_id'     => 0,
                    'receiver_id' => $user_id,
                    'content'     => '你与对方已解除好友关系，系统已禁止发送消息！',
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return di()->get(FormatMessageService::class)->handleChatRecords($rows);
    }

    /**
     * 获取转发会话记录信息
     *
     * @param int $user_id   用户ID
     * @param int $record_id 聊天记录ID
     * @return array
     */
    public function getForwardRecords(int $user_id, int $record_id): array
    {
        $result = TalkRecords::where('id', $record_id)->first([
            'id', 'talk_type', 'msg_type', 'user_id', 'receiver_id', 'content', 'is_revoke', 'created_at'
        ]);

        // 判断是否有权限查看
        if ($result->talk_type == TalkModeConstant::PRIVATE_CHAT && ($result->user_id != $user_id && $result->receiver_id != $user_id)) {
            return [];
        } else if ($result->talk_type == TalkModeConstant::GROUP_CHAT && !di()->get(GroupMemberService::class)->isMember($result->receiver_id, $user_id)) {
            return [];
        }

        $forward = TalkRecordsForward::where('record_id', $record_id)->first();

        $fields = [
            'talk_records.id',
            'talk_records.talk_type',
            'talk_records.msg_type',
            'talk_records.user_id',
            'talk_records.receiver_id',
            'talk_records.is_revoke',
            'talk_records.content',
            'talk_records.created_at',
            'users.nickname',
            'users.avatar as avatar',
        ];

        $rowsSqlObj = TalkRecords::select($fields);
        $rowsSqlObj->leftJoin('users', 'users.id', '=', 'talk_records.user_id');
        $rowsSqlObj->whereIn('talk_records.id', explode(',', $forward->records_id));
        $rows = $rowsSqlObj->get()->toArray();

        return di()->get(FormatMessageService::class)->handleChatRecords($rows);
    }

    /**
     * 批量删除聊天消息
     *
     * @param int   $user_id     用户ID
     * @param int   $talk_type   对话类型[1:好友消息;2:群聊消息;]
     * @param int   $receiver_id 好友ID或者群聊ID
     * @param array $record_ids  聊天记录ID
     * @return bool
     */
    public function removeRecords(int $user_id, int $talk_type, int $receiver_id, array $record_ids): bool
    {
        if ($talk_type == TalkModeConstant::PRIVATE_CHAT) {// 私聊信息
            $ids = TalkRecords::whereIn('id', $record_ids)->where(function ($query) use ($user_id, $receiver_id) {
                $query->where([['user_id', '=', $user_id], ['receiver_id', '=', $receiver_id]])
                    ->orWhere([['user_id', '=', $receiver_id], ['receiver_id', '=', $user_id]]);
            })->where('talk_type', $talk_type)->pluck('id');
        } else {// 群聊信息
            // 判读是否属于群消息并且判断是否是群成员
            if ($talk_type == TalkModeConstant::GROUP_CHAT && !di()->get(GroupMemberService::class)->isMember($receiver_id, $user_id)) {
                return false;
            }

            $ids = TalkRecords::whereIn('id', $record_ids)->where('talk_type', TalkModeConstant::GROUP_CHAT)->pluck('id');
        }

        // 判断要删除的消息在数据库中是否存在
        if (count($ids) != count($record_ids)) {
            return false;
        }

        $data = array_map(function ($record_id) use ($user_id) {
            return [
                'record_id'  => $record_id,
                'user_id'    => $user_id,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }, $ids->toArray());

        return Db::table('talk_records_delete')->insert($data);
    }

    /**
     * 撤回单条聊天消息
     *
     * @param int $user_id   用户ID
     * @param int $record_id 聊天记录ID
     * @return array
     */
    public function revokeRecord(int $user_id, int $record_id): array
    {
        $result = TalkRecords::where('id', $record_id)->first(['id', 'talk_type', 'user_id', 'receiver_id', 'created_at']);
        if (!$result) return [false, '消息记录不存在'];

        // 判断是否在两分钟之内撤回消息，超过2分钟不能撤回消息
        if ((time() - $result->created_at->timestamp > 120)) {
            return [false, '已超过有效的撤回时间', []];
        }

        if ($result->talk_type == TalkModeConstant::PRIVATE_CHAT) {
            if ($result->user_id != $user_id && $result->receiver_id != $user_id) {
                return [false, '非法操作', []];
            }
        } else if ($result->talk_type == TalkModeConstant::GROUP_CHAT) {
            if (!di()->get(GroupMemberService::class)->isMember($result->receiver_id, $user_id)) {
                return [false, '非法操作', []];
            }
        }

        $result->is_revoke = 1;
        $result->save();

        event()->dispatch(new TalkEvent(TalkEventConstant::EVENT_TALK_REVOKE, [
            'record_id' => $result->id
        ]));

        return [true, '消息已撤回', $result->toArray()];
    }

    /**
     * 关键词搜索聊天记录
     *
     * @param int   $user_id     用户ID
     * @param int   $receiver_id 接收者ID
     * @param int   $talk_type   对话类型[1:私信;2:群聊;]
     * @param int   $page        当前查询分页
     * @param int   $page_size   分页大小
     * @param array $params      查询参数
     * @return array
     */
    public function searchRecords(int $user_id, int $receiver_id, int $talk_type, int $page, int $page_size, array $params): array
    {
        $fields = [
            'talk_records.id',
            'talk_records.talk_type',
            'talk_records.msg_type',
            'talk_records.user_id',
            'talk_records.receiver_id',
            'talk_records.content',
            'talk_records.is_revoke',
            'talk_records.created_at',
            'users.nickname',
            'users.avatar as avatar',
        ];

        $model = TalkRecords::select($fields)->leftJoin('users', 'users.id', '=', 'talk_records.user_id');
        if ($talk_type == 1) {
            $model->where(function ($query) use ($user_id, $receiver_id) {
                $query->where([
                    ['talk_records.user_id', '=', $user_id],
                    ['talk_records.receiver_id', '=', $receiver_id]
                ])->orWhere([
                    ['talk_records.user_id', '=', $receiver_id],
                    ['talk_records.receiver_id', '=', $user_id]
                ]);
            });
        } else {
            $model->where('talk_records.receiver_id', $receiver_id);
        }

        $model->where('talk_records.talk_type', $talk_type);

        if (isset($params['keywords']) && !empty($params['keywords'])) {
            $model->where('talk_records.content', 'like', "%{$params['keywords']}%");
        }

        if (isset($params['date'])) {
            $model->whereDate('talk_records.created_at', $params['date']);
        }

        $count = $model->count();
        if ($count == 0) {
            return $this->getPagingRows([], 0, $page, $page_size);
        }

        $rows = $model->orderBy('talk_records.id', 'desc')->forPage($page, $page_size)->get()->toArray();

        $rows = di()->get(FormatMessageService::class)->handleChatRecords($rows);

        return $this->getPagingRows($rows, $count, $page, $page_size);
    }
}
