<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Talk\TalkRecordsCode;
use App\Model\Talk\TalkRecordsFile;
use Exception;
use App\Constant\TalkMessageType;
use App\Constant\TalkModeConstant;
use App\Model\Talk\TalkRecords;
use App\Model\Talk\TalkRecordsForward;
use Hyperf\DbConnection\Db;

class TalkForwardService extends BaseService
{
    /**
     * 验证消息转发
     *
     * @param int   $user_id     转发的用户ID
     * @param int   $receiver_id 当前转发消息的所属者(好友ID或者群聊ID)
     * @param int   $talk_type   消息来源  1:好友消息 2:群聊消息
     * @param array $records_ids 转发消息的记录ID
     * @return bool
     */
    public function verifyForward(int $user_id, int $receiver_id, int $talk_type, array $records_ids): bool
    {
        // 支持转发的消息类型
        $msg_type = TalkMessageType::getForwardTypes();

        $sqlObj = TalkRecords::whereIn('id', $records_ids);
        if ($talk_type == TalkModeConstant::PRIVATE_CHAT) {
            $sqlObj->where(function ($query) use ($user_id, $receiver_id) {
                $query->where([
                    ['user_id', '=', $user_id],
                    ['receiver_id', '=', $receiver_id]
                ])->orWhere([
                    ['user_id', '=', $receiver_id],
                    ['receiver_id', '=', $user_id]
                ]);
            });
        }

        $count = $sqlObj->where('talk_type', $talk_type)->whereIn('msg_type', $msg_type)->where('is_revoke', 0)->count();

        return $count == count($records_ids);
    }

    /**
     * 转发消息（多条合并转发）
     *
     * @param int   $user_id     转发的用户ID
     * @param int   $receiver_id 当前转发消息的所属者(好友ID或者群聊ID)
     * @param int   $talk_type   消息来源  1:好友消息 2:群聊消息
     * @param array $records_ids 转发消息的记录ID
     * @param array $receives    接受者数组  例如:[['talk_type' => 1,'id' => 3045]...] 二维数组
     * @return array
     */
    public function multiMergeForward(int $user_id, int $receiver_id, int $talk_type, array $records_ids, array $receives): array
    {
        $isTrue = $this->verifyForward($user_id, $receiver_id, $talk_type, $records_ids);
        if (!$isTrue) return [];

        // 默认取前3条聊天记录
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
            foreach ($receives as $item) {
                $res = TalkRecords::create([
                    'talk_type'   => $item['talk_type'],
                    'user_id'     => $user_id,
                    'receiver_id' => $item['id'],
                    'msg_type'    => TalkMessageType::FORWARD_MESSAGE,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);

                $insRecordIds[] = [
                    'record_id'   => $res->id,
                    'receiver_id' => $res->receiver_id,
                    'talk_type'   => $res->talk_type
                ];

                TalkRecordsForward::create([
                    'record_id'  => $res->id,
                    'user_id'    => $user_id,
                    'records_id' => implode(',', $records_ids),
                    'text'       => json_encode($jsonText),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return [];
        }

        return $insRecordIds;
    }

    /**
     * 转发消息（多条拆分转发）
     * @param int   $user_id     转发的用户ID
     * @param int   $receiver_id 当前转发消息的所属者(好友ID或者群聊ID)
     * @param int   $talk_type   消息来源  1:好友消息 2:群聊消息
     * @param array $records_ids 转发消息的记录ID
     * @param array $receives    接受者数组  例如:[['talk_type' => 1,'id' => 3045]...] 二维数组
     * @return array
     */
    public function multiSplitForward(int $user_id, int $receiver_id, int $talk_type, array $records_ids, array $receives): array
    {
        $isTrue = $this->verifyForward($user_id, $receiver_id, $talk_type, $records_ids);
        if (!$isTrue) return [];

        $rows = TalkRecords::whereIn('talk_records.id', $records_ids)
            ->get(['talk_records.id', 'talk_records.msg_type', 'talk_records.content']);

        if (empty($rows)) return [];

        $fileArray = $codeArray = [];
        foreach ($rows as $val) {
            if ($val['msg_type'] == TalkMessageType::FILE_MESSAGE) {
                $fileArray[] = $val['id'];
            } else if ($val['msg_type'] == TalkMessageType::CODE_MESSAGE) {
                $codeArray[] = $val['id'];
            }
        }

        if (!empty($fileArray)) {
            $fileArray = TalkRecordsFile::whereIn('record_id', $fileArray)->get()->keyBy('record_id')->toArray();
        }

        if (!empty($codeArray)) {
            $codeArray = TalkRecordsCode::whereIn('record_id', $codeArray)->get()->keyBy('record_id')->toArray();
        }

        $insRecordIds = [];
        Db::beginTransaction();
        try {
            $file = $code = [];
            foreach ($rows as $row) {
                foreach ($receives as $receive) {
                    $res = TalkRecords::create([
                        'talk_type'   => $receive['talk_type'],
                        'user_id'     => $user_id,
                        'receiver_id' => $receive['id'],
                        'msg_type'    => $row['msg_type'],
                        'content'     => $row['content'],
                        'created_at'  => date('Y-m-d H:i:s'),
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]);

                    $insRecordIds[] = [
                        'record_id'   => $res->id,
                        'receiver_id' => $res->receiver_id,
                        'talk_type'   => $res->talk_type
                    ];

                    switch ($row['msg_type']) {
                        case TalkMessageType::FILE_MESSAGE:
                            $fileInfo = $fileArray[$row['id']];
                            $file[]   = [
                                'record_id'     => $res->id,
                                'user_id'       => $fileInfo['user_id'],
                                'file_source'   => $fileInfo['file_source'],
                                'file_type'     => $fileInfo['file_type'],
                                'save_type'     => $fileInfo['save_type'],
                                'original_name' => $fileInfo['original_name'],
                                'file_suffix'   => $fileInfo['file_suffix'],
                                'file_size'     => $fileInfo['file_size'],
                                'save_dir'      => $fileInfo['save_dir'],
                                'created_at'    => date('Y-m-d H:i:s')
                            ];
                            break;
                        case TalkMessageType::CODE_MESSAGE:
                            $codeInfo = $codeArray[$row['id']];
                            $code[]   = [
                                'record_id'  => $res->id,
                                'user_id'    => $user_id,
                                'code_lang'  => $codeInfo['lang'],
                                'code'       => $codeInfo['code'],
                                'created_at' => date('Y-m-d H:i:s')
                            ];
                            break;
                    }
                }
            }

            $code && TalkRecordsCode::insert($code);
            $file && TalkRecordsFile::insert($file);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            return [];
        }

        return $insRecordIds;
    }
}
