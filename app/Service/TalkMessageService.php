<?php
declare(strict_types=1);

namespace App\Service;

use App\Cache\LastMessage;
use App\Cache\UnreadTalkCache;
use App\Cache\VoteCache;
use App\Cache\VoteStatisticsCache;
use App\Constant\RobotConstant;
use App\Constant\TalkEventConstant;
use App\Constant\TalkMessageType;
use App\Constant\TalkModeConstant;
use App\Event\TalkEvent;
use App\Model\Group\GroupMember;
use App\Model\Talk\TalkRecordsCode;
use App\Model\Talk\TalkRecordsLogin;
use App\Model\Talk\TalkRecordsVote;
use App\Model\Talk\TalkRecordsVoteAnswer;
use App\Repository\RobotRepository;
use App\Support\UserRelation;
use Exception;
use App\Constant\MediaTypeConstant;
use App\Model\Talk\TalkRecords;
use App\Model\Talk\TalkRecordsFile;
use Hyperf\DbConnection\Db;

class TalkMessageService
{
    /**
     * 创建文本消息
     *
     * @param array $message
     * @return bool
     */
    public function insertText(array $message): bool
    {
        $message['msg_type'] = TalkMessageType::TEXT_MESSAGE;
        $message['content']  = htmlspecialchars($message['content']);

        $result = TalkRecords::create($message);

        $this->handle($result, ['text' => mb_substr($result->content, 0, 30)]);

        return true;
    }

    /**
     * 创建代码块消息
     *
     * @param array $message
     * @param array $code
     * @return bool
     */
    public function insertCode(array $message, array $code): bool
    {
        Db::beginTransaction();
        try {
            $message['msg_type'] = TalkMessageType::CODE_MESSAGE;

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

        $this->handle($insert, ['text' => '[代码消息]']);

        return true;
    }

    /**
     * 创建文件类消息
     *
     * @param array $message
     * @param array $file
     * @return bool
     */
    public function insertFile(array $message, array $file): bool
    {
        Db::beginTransaction();
        try {
            $message['msg_type'] = TalkMessageType::FILE_MESSAGE;

            $insert = TalkRecords::create($message);
            if (!$insert) {
                throw new Exception('插入聊天记录失败...');
            }

            $file['record_id']  = $insert->id;
            $file['type']       = MediaTypeConstant::getMediaType($file['suffix']);
            $file['created_at'] = date('Y-m-d H:i:s');

            if (!TalkRecordsFile::create($file)) {
                throw new Exception('插入聊天记录(代码消息)失败...');
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            logger()->error($e->getMessage());
            return false;
        }

        $this->handle($insert, ['text' => '[文件消息]']);

        return true;
    }

    /**
     * 添加投票消息
     *
     * @param array $message
     * @param array $vote
     * @return bool
     */
    public function insertVote(array $message, array $vote): bool
    {
        $answer_num = GroupMember::where('group_id', $message['receiver_id'])->where('is_quit', 0)->count();

        Db::beginTransaction();
        try {
            $message['msg_type'] = TalkMessageType::VOTE_MESSAGE;
            $insert              = TalkRecords::create($message);

            $options = [];
            foreach ($vote['answer_option'] as $k => $option) {
                $options[chr(65 + $k)] = $option;
            }

            $vote['record_id']     = $insert->id;
            $vote['answer_option'] = $options;
            $vote['answer_num']    = $answer_num;

            if (!TalkRecordsVote::create($vote)) {
                throw new Exception('插入聊天记录(投票消息)失败...');
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return false;
        }

        $this->handle($insert, ['text' => '[投票消息]']);

        return true;
    }

    /**
     * 群投票处理方法
     *
     * @param int   $user_id
     * @param array $params
     * @return array
     */
    public function handleVote(int $user_id, array $params): array
    {
        $record = TalkRecords::join('talk_records_vote as vote', 'vote.record_id', '=', 'talk_records.id')
            ->where('talk_records.id', $params['record_id'])
            ->withCasts([
                'answer_option' => 'array'
            ])
            ->first([
                'talk_records.id', 'talk_records.receiver_id', 'talk_records.talk_type', 'talk_records.msg_type',
                'vote.id as vote_id', 'vote.answer_mode', 'vote.answer_option', 'vote.answer_num', 'vote.status as vote_status'
            ]);

        if (!$record) return [false, []];

        if ($record->msg_type != TalkMessageType::VOTE_MESSAGE) {
            return [false, []];
        }

        if (!UserRelation::isFriendOrGroupMember($user_id, $record->receiver_id, $record->talk_type)) {
            return [false, []];
        }

        if (TalkRecordsVoteAnswer::where('vote_id', $record->vote_id)->where('user_id', $user_id)->exists()) {
            return [false, []];
        }

        $options = $params['options'];

        sort($options);

        foreach ($options as $value) {
            if (!isset($record->answer_option[$value])) return [false, []];
        }

        // 单选模式取第一个
        if ($record->answer_mode == 0) {
            $options = [$options[0]];
        }

        try {
            Db::transaction(function () use ($options, $record, $user_id) {
                TalkRecordsVote::where('id', $record->vote_id)->update([
                    'answered_num' => Db::raw('answered_num + 1'),
                    'status'       => Db::raw('if(answered_num >= answer_num, 1, 0)'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);

                foreach ($options as $option) {
                    TalkRecordsVoteAnswer::create([
                        'vote_id'    => $record->vote_id,
                        'user_id'    => $user_id,
                        'option'     => $option,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            });
        } catch (\Exception $e) {
            return [false, []];
        }

        // 更新投票缓存
        VoteCache::getInstance()->updateCache($record->vote_id);

        $cache = VoteStatisticsCache::getInstance()->updateVoteCache($record->vote_id);

        // todo 推送消息

        return [true, $cache];
    }

    /**
     * 添加登录消息
     *
     * @param array $message
     * @param array $loginParams
     * @return bool
     */
    public function insertLogin(array $message, array $loginParams): bool
    {
        $user_id = di()->get(RobotRepository::class)->findTypeByUserId(RobotConstant::LOGIN_ROBOT);

        if ($user_id == 0) return false;

        Db::beginTransaction();
        try {
            $message['user_id']   = $user_id;
            $message['talk_type'] = TalkModeConstant::PRIVATE_CHAT;
            $message['msg_type']  = TalkMessageType::USER_LOGIN_MESSAGE;

            $insert = TalkRecords::create($message);
            if (!$insert) {
                throw new Exception('插入聊天记录失败...');
            }

            $loginParams['record_id']  = $insert->id;
            $loginParams['created_at'] = date('Y-m-d H:i:s');

            if (!TalkRecordsLogin::create($loginParams)) {
                throw new Exception('插入聊天记录(登录消息)失败...');
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            return false;
        }

        // 创建对话列表
        di()->get(TalkSessionService::class)->create($insert->receiver_id, $insert->user_id, $insert->talk_type, true);

        $this->handle($insert, ['text' => '[登录提醒]']);

        return true;
    }

    /**
     * 处理数据
     *
     * @param TalkRecords $record
     * @param array       $option
     */
    private function handle(TalkRecords $record, array $option = []): void
    {
        if ($record->talk_type == TalkModeConstant::PRIVATE_CHAT) {
            UnreadTalkCache::getInstance()->increment($record->user_id, $record->receiver_id);
        }

        LastMessage::getInstance()->save($record->talk_type, $record->user_id, $record->receiver_id, [
            'text'       => $option['text'],
            'created_at' => date('Y-m-d H:i:s')
        ]);

        event()->dispatch(new TalkEvent(TalkEventConstant::EVENT_TALK, [
            'sender_id'   => $record->user_id,
            'receiver_id' => $record->receiver_id,
            'talk_type'   => $record->talk_type,
            'record_id'   => $record->id
        ]));
    }
}
