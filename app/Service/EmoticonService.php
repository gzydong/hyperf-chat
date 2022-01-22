<?php
declare(strict_types=1);

namespace App\Service;

use App\Constant\TalkMessageType;
use App\Constant\TalkModeConstant;
use App\Model\Talk\TalkRecords;
use App\Model\Talk\TalkRecordsFile;
use App\Model\Emoticon\EmoticonItem;
use App\Model\Emoticon\UsersEmoticon;
use App\Service\Group\GroupMemberService;

/**
 * 表情服务层
 * Class EmoticonService
 *
 * @package App\Services
 */
class EmoticonService extends BaseService
{
    /**
     * 安装系统表情包
     *
     * @param int $user_id     用户ID
     * @param int $emoticon_id 表情包ID
     * @return bool
     */
    public function installSysEmoticon(int $user_id, int $emoticon_id): bool
    {
        $info = UsersEmoticon::select(['id', 'user_id', 'emoticon_ids'])->where('user_id', $user_id)->first();
        if (!$info) {
            return (bool)UsersEmoticon::create(['user_id' => $user_id, 'emoticon_ids' => $emoticon_id]);
        }

        $emoticon_ids = $info->emoticon_ids;
        if (in_array($emoticon_id, $emoticon_ids)) {
            return true;
        }

        $emoticon_ids[] = $emoticon_id;

        return (bool)UsersEmoticon::where('user_id', $user_id)->update([
            'emoticon_ids' => implode(',', $emoticon_ids)
        ]);
    }

    /**
     * 移除已安装的系统表情包
     *
     * @param int $user_id     用户ID
     * @param int $emoticon_id 表情包ID
     * @return bool
     */
    public function removeSysEmoticon(int $user_id, int $emoticon_id): bool
    {
        $info = UsersEmoticon::select(['id', 'user_id', 'emoticon_ids'])->where('user_id', $user_id)->first();
        if (!$info || !in_array($emoticon_id, $info->emoticon_ids)) {
            return false;
        }

        $emoticon_ids = $info->emoticon_ids;
        foreach ($emoticon_ids as $k => $id) {
            if ($id == $emoticon_id) unset($emoticon_ids[$k]);
        }

        if (count($info->emoticon_ids) == count($emoticon_ids)) {
            return false;
        }

        return (bool)UsersEmoticon::where('user_id', $user_id)->update([
            'emoticon_ids' => implode(',', $emoticon_ids)
        ]);
    }

    /**
     * 获取用户安装的表情ID
     *
     * @param int $user_id 用户ID
     * @return array
     */
    public function getInstallIds(int $user_id): array
    {
        $result = UsersEmoticon::where('user_id', $user_id)->value('emoticon_ids');

        return $result ? array_filter($result) : [];
    }

    /**
     * 收藏聊天图片
     *
     * @param int $user_id   用户ID
     * @param int $record_id 聊天消息ID
     * @return array
     */
    public function collect(int $user_id, int $record_id): array
    {
        $result = TalkRecords::where([
            ['id', '=', $record_id],
            ['msg_type', '=', TalkMessageType::FILE_MESSAGE],
            ['is_revoke', '=', 0],
        ])->first(['id', 'talk_type', 'receiver_id', 'msg_type', 'user_id', 'is_revoke']);

        if (!$result) return [false, []];

        if ($result->talk_type == TalkModeConstant::PRIVATE_CHAT) {
            if ($result->user_id != $user_id && $result->receiver_id != $user_id) {
                return [false, []];
            }
        } else {
            if (!di()->get(GroupMemberService::class)->isMember($result->receiver_id, $user_id)) {
                return [false, []];
            }
        }

        $fileInfo = TalkRecordsFile::where('record_id', $result->id)->where('file_type', 1)->first([
            'file_suffix',
            'file_size',
            'save_dir'
        ]);

        if (!$fileInfo) return [false, []];

        $result = EmoticonItem::where('user_id', $user_id)->where('url', $fileInfo->save_dir)->first();
        if ($result) return [false, []];

        $res = EmoticonItem::create([
            'user_id'     => $user_id,
            'url'         => $fileInfo->save_dir,
            'file_suffix' => $fileInfo->file_suffix,
            'file_size'   => $fileInfo->file_size,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return $res ? [true, ['media_id' => $res->id, 'src' => get_media_url($res->url)]] : [false, []];
    }

    /**
     * 移除收藏的表情包
     *
     * @param int   $user_id 用户ID
     * @param array $ids     表情包详情ID
     * @return bool
     * @throws \Exception
     */
    public function deleteCollect(int $user_id, array $ids)
    {
        return EmoticonItem::whereIn('id', $ids)->where('user_id', $user_id)->delete();
    }

    /**
     * 获取表情包列表
     *
     * @param array $where
     * @return array
     */
    public function getDetailsAll(array $where = []): array
    {
        return EmoticonItem::where($where)->get(['id as media_id', 'url as src'])->toArray();
    }
}
