<?php

namespace App\Service;

use App\Constants\TalkMessageType;
use App\Constants\TalkMode;
use Exception;
use App\Model\User;
use App\Model\UsersFriend;
use App\Model\Group\Group;
use App\Model\Chat\TalkRecords;
use App\Model\Chat\TalkRecordsCode;
use App\Model\Chat\TalkRecordsFile;
use App\Model\Chat\TalkRecordsForward;
use App\Model\Chat\TalkRecordsInvite;
use App\Traits\PagingTrait;
use Hyperf\DbConnection\Db;

class TalkService extends BaseService
{
    use PagingTrait;

    /**
     * 处理聊天记录信息
     *
     * @param array $rows 聊天记录
     * @return array
     */
    public function handleChatRecords(array $rows)
    {
        if (empty($rows)) return [];

        $files = $codes = $forwards = $invites = [];
        foreach ($rows as $value) {
            switch ($value['msg_type']) {
                case TalkMessageType::FILE_MESSAGE:
                    $files[] = $value['id'];
                    break;
                case TalkMessageType::GROUP_INVITE_MESSAGE:
                    $invites[] = $value['id'];
                    break;
                case TalkMessageType::FORWARD_MESSAGE:
                    $forwards[] = $value['id'];
                    break;
                case TalkMessageType::CODE_MESSAGE:
                    $codes[] = $value['id'];
                    break;
            }
        }

        // 查询聊天文件信息
        if ($files) {
            $files = TalkRecordsFile::whereIn('record_id', $files)->get(['id', 'record_id', 'user_id', 'file_source', 'file_type', 'save_type', 'original_name', 'file_suffix', 'file_size', 'save_dir'])->keyBy('record_id')->toArray();
        }

        // 查询群聊邀请信息
        if ($invites) {
            $invites = TalkRecordsInvite::whereIn('record_id', $invites)->get(['record_id', 'type', 'operate_user_id', 'user_ids'])->keyBy('record_id')->toArray();
        }

        // 查询代码块消息
        if ($codes) {
            $codes = TalkRecordsCode::whereIn('record_id', $codes)->get(['record_id', 'code_lang', 'code'])->keyBy('record_id')->toArray();
        }

        // 查询消息转发信息
        if ($forwards) {
            $forwards = TalkRecordsForward::whereIn('record_id', $forwards)->get(['record_id', 'records_id', 'text'])->keyBy('record_id')->toArray();
        }

        foreach ($rows as $k => $row) {
            $rows[$k]['file']       = [];
            $rows[$k]['code_block'] = [];
            $rows[$k]['forward']    = [];
            $rows[$k]['invite']     = [];

            switch ($row['msg_type']) {
                case TalkMessageType::FILE_MESSAGE:// 文件消息
                    $rows[$k]['file'] = $files[$row['id']] ?? [];
                    if ($rows[$k]['file']) {
                        $rows[$k]['file']['file_url'] = get_media_url($rows[$k]['file']['save_dir']);
                    }
                    break;

                case TalkMessageType::FORWARD_MESSAGE:// 会话记录消息
                    if (isset($forwards[$row['id']])) {
                        $rows[$k]['forward'] = [
                            'num'  => substr_count($forwards[$row['id']]['records_id'], ',') + 1,
                            'list' => json_decode($forwards[$row['id']]['text'], true) ?? []
                        ];
                    }
                    break;

                case TalkMessageType::CODE_MESSAGE:// 代码块消息
                    $rows[$k]['code_block'] = $codes[$row['id']] ?? [];
                    if ($rows[$k]['code_block']) {
                        $rows[$k]['code_block']['code'] = htmlspecialchars_decode($rows[$k]['code_block']['code']);
                        unset($rows[$k]['code_block']['record_id']);
                    }
                    break;

                case TalkMessageType::GROUP_INVITE_MESSAGE:// 入群消息/退群消息
                    if (isset($invites[$row['id']])) {
                        $rows[$k]['invite'] = [
                            'type'         => $invites[$row['id']]['type'],
                            'operate_user' => [
                                'id'       => $invites[$row['id']]['operate_user_id'],
                                'nickname' => User::where('id', $invites[$row['id']]['operate_user_id'])->value('nickname')
                            ],
                            'users'        => []
                        ];

                        if ($rows[$k]['invite']['type'] == 1 || $rows[$k]['invite']['type'] == 3) {
                            $rows[$k]['invite']['users'] = User::select('id', 'nickname')->whereIn('id', parse_ids($invites[$row['id']]['user_ids']))->get()->toArray();
                        } else {
                            $rows[$k]['invite']['users'] = $rows[$k]['invite']['operate_user'];
                        }
                    }
                    break;
            }
        }

        unset($files, $codes, $forwards, $invites);
        return $rows;
    }

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
    public function getChatRecords(int $user_id, int $receiver_id, int $talk_type, int $record_id, $limit = 30, $msg_type = [])
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

        $rowsSqlObj = TalkRecords::select($fields);

        $rowsSqlObj->leftJoin('users', 'users.id', '=', 'talk_records.user_id');
        if ($record_id) {
            $rowsSqlObj->where('talk_records.id', '<', $record_id);
        }

        if ($talk_type == TalkMode::PRIVATE_CHAT) {
            $rowsSqlObj->where(function ($query) use ($user_id, $receiver_id) {
                $query->where([
                    ['talk_records.user_id', '=', $user_id],
                    ['talk_records.receiver_id', '=', $receiver_id]
                ])->orWhere([
                    ['talk_records.user_id', '=', $receiver_id],
                    ['talk_records.receiver_id', '=', $user_id]
                ]);
            });
        } else {
            $rowsSqlObj->where('talk_records.receiver_id', $receiver_id);
            $rowsSqlObj->where('talk_records.talk_type', $talk_type);
        }

        if ($msg_type) {
            $rowsSqlObj->whereIn('talk_records.msg_type', $msg_type);
        }

        //过滤用户删除记录
        $rowsSqlObj->whereNotExists(function ($query) use ($user_id) {
            $prefix = config('databases.default.prefix');
            $query->select(Db::raw(1))->from('talk_records_delete');
            $query->whereRaw("{$prefix}talk_records_delete.record_id = {$prefix}talk_records.id and {$prefix}talk_records_delete.user_id = {$user_id}");
            $query->limit(1);
        });

        return $this->handleChatRecords(
            $rowsSqlObj->orderBy('talk_records.id', 'desc')->limit($limit)->get()->toArray()
        );
    }

    /**
     * 获取转发会话记录信息
     *
     * @param int $user_id   用户ID
     * @param int $record_id 聊天记录ID
     * @return array
     */
    public function getForwardRecords(int $user_id, int $record_id)
    {
        $result = TalkRecords::where('id', $record_id)->first([
            'id', 'talk_type', 'msg_type', 'user_id', 'receiver_id', 'content', 'is_revoke', 'created_at'
        ]);

        // 判断是否有权限查看
        if ($result->talk_type == TalkMode::PRIVATE_CHAT && ($result->user_id != $user_id && $result->receiver_id != $user_id)) {
            return [];
        } else if ($result->talk_type == TalkMode::GROUP_CHAT && !Group::isMember($result->receiver_id, $user_id)) {
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

        return $this->handleChatRecords($rowsSqlObj->get()->toArray());
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
    public function removeRecords(int $user_id, int $talk_type, int $receiver_id, array $record_ids)
    {
        if ($talk_type == TalkMode::PRIVATE_CHAT) {// 私聊信息
            $ids = TalkRecords::whereIn('id', $record_ids)->where(function ($query) use ($user_id, $receiver_id) {
                $query->where([['user_id', '=', $user_id], ['receiver_id', '=', $receiver_id]])
                    ->orWhere([['user_id', '=', $receiver_id], ['receiver_id', '=', $user_id]]);
            })->where('talk_type', $talk_type)->pluck('id');
        } else {// 群聊信息
            $ids = TalkRecords::whereIn('id', $record_ids)->where('talk_type', TalkMode::GROUP_CHAT)->pluck('id');
        }

        // 判断要删除的消息在数据库中是否存在
        if (count($ids) != count($record_ids)) {
            return false;
        }

        // 判读是否属于群消息并且判断是否是群成员
        if ($talk_type == TalkMode::GROUP_CHAT && !Group::isMember($receiver_id, $user_id)) {
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
    public function revokeRecord(int $user_id, int $record_id)
    {
        $result = TalkRecords::where('id', $record_id)->first(['id', 'talk_type', 'user_id', 'receiver_id', 'created_at']);
        if (!$result) return [false, '消息记录不存在'];

        // 判断是否在两分钟之内撤回消息，超过2分钟不能撤回消息
        if ((time() - strtotime($result->created_at) > 120)) {
            return [false, '已超过有效的撤回时间', []];
        }

        if ($result->talk_type == TalkMode::PRIVATE_CHAT) {
            if ($result->user_id != $user_id && $result->receiver_id != $user_id) {
                return [false, '非法操作', []];
            }
        } else if ($result->talk_type == TalkMode::GROUP_CHAT) {
            if (!Group::isMember($result->receiver_id, $user_id)) {
                return [false, '非法操作', []];
            }
        }

        $result->is_revoke = 1;
        $result->save();

        return [true, '消息已撤回', $result->toArray()];
    }

    /**
     * 转发消息（单条转发）
     *
     * @param int   $user_id      转发的用户ID
     * @param int   $record_id    转发消息的记录ID
     * @param array $receiver_ids 接受者数组  例如:[['talk_type' => 1,'id' => 3045]...] 二维数组
     * @return array
     */
    public function forwardRecords(int $user_id, int $record_id, array $receiver_ids)
    {
        $msgTypeArray = [
            TalkMessageType::TEXT_MESSAGE,
            TalkMessageType::FILE_MESSAGE,
            TalkMessageType::CODE_MESSAGE
        ];

        $result = TalkRecords::where('id', $record_id)->whereIn('msg_type', $msgTypeArray)->first();
        if (!$result) return [];

        // 根据消息类型判断用户是否有转发权限
        if ($result->talk_type == TalkMode::PRIVATE_CHAT) {
            if ($result->user_id != $user_id && $result->receiver_id != $user_id) {
                return [];
            }
        } else if ($result->talk_type == TalkMode::GROUP_CHAT) {
            if (!Group::isMember($result->receiver_id, $user_id)) {
                return [];
            }
        }

        $fileInfo = $codeBlock = null;
        if ($result->msg_type == TalkMessageType::FILE_MESSAGE) {
            $fileInfo = TalkRecordsFile::where('record_id', $record_id)->first();
        } else if ($result->msg_type == TalkMessageType::CODE_MESSAGE) {
            $codeBlock = TalkRecordsCode::where('record_id', $record_id)->first();
        }

        $insRecordIds = [];
        Db::beginTransaction();
        try {
            foreach ($receiver_ids as $item) {
                $res = TalkRecords::create([
                    'talk_type'   => $item['talk_type'],
                    'msg_type'    => $result->msg_type,
                    'user_id'     => $user_id,
                    'receiver_id' => $item['id'],
                    'content'     => $result->content,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);

                if (!$res) {
                    throw new Exception('插入消息记录失败');
                }

                $insRecordIds[] = [
                    'record_id'   => $res->id,
                    'receiver_id' => $res->receiver_id,
                    'talk_type'   => $res->talk_type
                ];

                if ($result->msg_type == TalkMessageType::FILE_MESSAGE) {
                    if (!TalkRecordsFile::create([
                        'record_id'     => $res->id,
                        'user_id'       => $fileInfo->user_id,
                        'file_source'   => $fileInfo->file_source,
                        'file_type'     => $fileInfo->file_type,
                        'save_type'     => $fileInfo->save_type,
                        'original_name' => $fileInfo->original_name,
                        'file_suffix'   => $fileInfo->file_suffix,
                        'file_size'     => $fileInfo->file_size,
                        'save_dir'      => $fileInfo->save_dir,
                        'created_at'    => date('Y-m-d H:i:s')
                    ])) {
                        throw new Exception('插入文件消息记录失败');
                    }
                } else if ($result->msg_type == TalkMessageType::CODE_MESSAGE) {
                    if (!TalkRecordsCode::create([
                        'record_id'  => $res->id,
                        'user_id'    => $user_id,
                        'code_lang'  => $codeBlock->code_lang,
                        'code'       => $codeBlock->code,
                        'created_at' => date('Y-m-d H:i:s')
                    ])) {
                        throw new Exception('插入代码消息记录失败');
                    }
                }
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return [];
        }

        return $insRecordIds;
    }

    /**
     * 转发消息（多条合并转发）
     *
     * @param int   $user_id     转发的用户ID
     * @param int   $receiver_id 当前转发消息的所属者(好友ID或者群聊ID)
     * @param int   $talk_type   消息来源  1:好友消息 2:群聊消息
     * @param array $records_ids 转发消息的记录ID
     * @param array $receive_ids 接受者数组  例如:[['talk_type' => 1,'id' => 3045]...] 二维数组
     * @return array
     */
    public function mergeForwardRecords(int $user_id, int $receiver_id, int $talk_type, array $records_ids, array $receive_ids)
    {
        // 支持转发的消息类型
        $msg_type = [
            TalkMessageType::TEXT_MESSAGE,
            TalkMessageType::FILE_MESSAGE,
            TalkMessageType::CODE_MESSAGE
        ];

        $sqlObj = TalkRecords::whereIn('id', $records_ids);

        if ($talk_type == TalkMode::PRIVATE_CHAT) {
            if (!UsersFriend::isFriend($user_id, $receiver_id)) return [];

            $sqlObj = $sqlObj->where(function ($query) use ($user_id, $receiver_id) {
                $query->where([
                    ['user_id', '=', $user_id],
                    ['receiver_id', '=', $receiver_id]
                ])->orWhere([
                    ['user_id', '=', $receiver_id],
                    ['receiver_id', '=', $user_id]
                ]);
            })->whereIn('msg_type', $msg_type)->where('talk_type', $talk_type)->where('is_revoke', 0);
        } else {
            if (!Group::isMember($receiver_id, $user_id)) return [];

            $sqlObj = $sqlObj->where('receiver_id', $receiver_id)->whereIn('msg_type', $msg_type)->where('talk_type', TalkMode::GROUP_CHAT)->where('is_revoke', 0);
        }

        $result = $sqlObj->get();

        // 判断消息记录是否存在
        if (count($result) != count($records_ids)) {
            return [];
        }

        $rows = TalkRecords::leftJoin('users', 'users.id', '=', 'talk_records.user_id')
            ->whereIn('talk_records.id', array_slice($records_ids, 0, 3))
            ->get(['talk_records.msg_type', 'talk_records.content', 'users.nickname']);

        $jsonText = [];
        foreach ($rows as $row) {
            switch ($row->msg_type) {
                case TalkMessageType::TEXT_MESSAGE:
                    $jsonText[] = [
                        'nickname' => $row->nickname,
                        'text'     => mb_substr(str_replace(PHP_EOL, "", $row->content), 0, 30)
                    ];
                    break;
                case TalkMessageType::FILE_MESSAGE:
                    $jsonText[] = [
                        'nickname' => $row->nickname,
                        'text'     => '【文件消息】'
                    ];
                    break;
                case TalkMessageType::CODE_MESSAGE:
                    $jsonText[] = [
                        'nickname' => $row->nickname,
                        'text'     => '【代码消息】'
                    ];
                    break;
            }
        }

        $insRecordIds = [];
        Db::beginTransaction();
        try {
            foreach ($receive_ids as $item) {
                $res = TalkRecords::create([
                    'talk_type'   => $item['talk_type'],
                    'user_id'     => $user_id,
                    'receiver_id' => $item['id'],
                    'msg_type'    => TalkMessageType::FORWARD_MESSAGE,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);

                if (!$res) {
                    throw new Exception('插入消息失败');
                }

                $insRecordIds[] = [
                    'record_id'   => $res->id,
                    'receiver_id' => $res->receiver_id,
                    'talk_type'   => $res->talk_type
                ];

                if (!TalkRecordsForward::create([
                    'record_id'  => $res->id,
                    'user_id'    => $user_id,
                    'records_id' => implode(',', $records_ids),
                    'text'       => json_encode($jsonText),
                    'created_at' => date('Y-m-d H:i:s'),
                ])) {
                    throw new Exception('插入转发消息失败');
                }
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return [];
        }

        return $insRecordIds;
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
    public function searchRecords(int $user_id, int $receiver_id, int $talk_type, int $page, int $page_size, array $params)
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

        $rowsSqlObj = TalkRecords::select($fields)->leftJoin('users', 'users.id', '=', 'talk_records.user_id');
        if ($talk_type == 1) {
            $rowsSqlObj->where(function ($query) use ($user_id, $receiver_id) {
                $query->where([
                    ['talk_records.user_id', '=', $user_id],
                    ['talk_records.receiver_id', '=', $receiver_id]
                ])->orWhere([
                    ['talk_records.user_id', '=', $receiver_id],
                    ['talk_records.receiver_id', '=', $user_id]
                ]);
            });
        } else {
            $rowsSqlObj->where('talk_records.receiver_id', $receiver_id);
            $rowsSqlObj->where('talk_records.talk_type', $talk_type);
        }

        if (isset($params['keywords'])) {
            $rowsSqlObj->where('talk_records.content', 'like', "%{$params['keywords']}%");
        }

        if (isset($params['date'])) {
            $rowsSqlObj->whereDate('talk_records.created_at', $params['date']);
        }

        $count = $rowsSqlObj->count();
        if ($count == 0) {
            return $this->getPagingRows([], 0, $page, $page_size);
        }

        $rows = $rowsSqlObj->orderBy('talk_records.id', 'desc')->forPage($page, $page_size)->get()->toArray();
        return $this->getPagingRows($this->handleChatRecords($rows), $count, $page, $page_size);
    }
}
