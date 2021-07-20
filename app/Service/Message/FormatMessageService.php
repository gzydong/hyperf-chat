<?php
declare(strict_types=1);

namespace App\Service\Message;

use App\Cache\VoteCache;
use App\Cache\VoteStatisticsCache;
use App\Constants\TalkMessageType;
use App\Model\Talk\TalkRecordsCode;
use App\Model\Talk\TalkRecordsFile;
use App\Model\Talk\TalkRecordsForward;
use App\Model\Talk\TalkRecordsInvite;
use App\Model\Talk\TalkRecordsVote;
use App\Model\User;

class FormatMessageService
{
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
            "vote"         => [],

            // 消息创建时间
            "content"      => '',// 文本消息
            "created_at"   => "",

            // 消息属性
            "is_revoke"    => 0, // 消息是否撤销
        ];

        return array_merge($message, array_intersect_key($data, $message));
    }

    /**
     * 处理聊天记录信息
     *
     * @param array $rows 聊天记录
     * @return array
     */
    public function handleChatRecords(array $rows)
    {
        if (empty($rows)) return [];

        $files = $codes = $forwards = $invites = $votes = [];
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
                case TalkMessageType::VOTE_MESSAGE:
                    $votes[] = $value['id'];
                    break;
                default:
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

        if ($votes) {
            $votes = TalkRecordsVote::whereIn('record_id', $votes)->get([
                'id', 'record_id', 'title', 'answer_mode', 'status', 'answer_option', 'answer_num', 'answered_num'
            ])->keyBy('record_id')->toArray();
        }

        foreach ($rows as $k => $row) {
            $rows[$k]['file']       = [];
            $rows[$k]['code_block'] = [];
            $rows[$k]['forward']    = [];
            $rows[$k]['invite']     = [];
            $rows[$k]['vote']       = [];

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

                case TalkMessageType::VOTE_MESSAGE:// 投票消息
                    $options = [];
                    foreach ($votes[$row['id']]['answer_option'] as $k2 => $value) {
                        $options[] = [
                            'key'   => $k2,
                            'value' => $value
                        ];
                    }

                    $votes[$row['id']]['answer_option'] = $options;
                    $rows[$k]['vote']                   = [
                        'statistics' => VoteStatisticsCache::getInstance()->getOrSetVoteCache($votes[$row['id']]['id']),
                        'vote_users' => VoteCache::getInstance()->getOrSetVoteCache($votes[$row['id']]['id']),
                        'detail'     => $votes[$row['id']],
                    ];
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

            $rows[$k] = $this->formatTalkMessage($rows[$k]);
        }

        return $rows;
    }
}
