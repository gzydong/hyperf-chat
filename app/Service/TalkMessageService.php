<?php

namespace App\Service;

use App\Constants\TalkMessageType;
use App\Model\Talk\TalkRecordsCode;
use App\Model\Talk\TalkRecordsVote;
use Exception;
use App\Constants\MediaFileType;
use App\Model\Talk\TalkRecords;
use App\Model\Talk\TalkRecordsFile;
use Hyperf\DbConnection\Db;

class TalkMessageService
{
    /**
     * 创建代码块消息
     *
     * @param array $message
     * @param array $code
     * @return bool|int
     */
    public function insertCodeMessage(array $message, array $code)
    {
        Db::beginTransaction();
        try {
            $message['msg_type']   = TalkMessageType::CODE_MESSAGE;
            $message['created_at'] = date('Y-m-d H:i:s');
            $message['updated_at'] = date('Y-m-d H:i:s');

            $insert = TalkRecords::create($message);
            if (!$insert) {
                throw new Exception('插入聊天记录失败...');
            }

            $code['record_id']  = $insert->id;
            $code['created_at'] = date('Y-m-d H:i:s');
            if (!TalkRecordsCode::create($code)) {
                throw new Exception('插入聊天记录(代码消息)失败...');
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return false;
        }

        return $insert->id;
    }

    /**
     * 创建文件类消息
     *
     * @param array $message
     * @param array $file
     * @return bool|int
     */
    public function insertFileMessage(array $message, array $file)
    {
        Db::beginTransaction();
        try {
            $message['msg_type']   = TalkMessageType::FILE_MESSAGE;
            $message['created_at'] = date('Y-m-d H:i:s');
            $message['updated_at'] = date('Y-m-d H:i:s');

            $insert = TalkRecords::create($message);
            if (!$insert) {
                throw new Exception('插入聊天记录失败...');
            }

            $file['record_id']  = $insert->id;
            $file['file_type']  = MediaFileType::getMediaType($file['file_suffix']);
            $file['created_at'] = date('Y-m-d H:i:s');
            if (!TalkRecordsFile::create($file)) {
                throw new Exception('插入聊天记录(代码消息)失败...');
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return false;
        }

        return $insert->id;
    }

    /**
     * @param array $message
     * @param array $vote
     */
    public function insertVoteMessage(array $message, array $vote)
    {
        Db::beginTransaction();
        try {
            $message['msg_type']   = TalkMessageType::FILE_MESSAGE;
            $message['created_at'] = date('Y-m-d H:i:s');
            $message['updated_at'] = date('Y-m-d H:i:s');

            $insert = TalkRecords::create($message);
            if (!$insert) {
                throw new Exception('插入聊天记录失败...');
            }

            $vote['record_id'] = $insert->id;
            if (!TalkRecordsVote::create($vote)) {
                throw new Exception('插入聊天记录(投票消息)失败...');
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return false;
        }

        return $insert->id;
    }
}
