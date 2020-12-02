<?php

namespace App\Service;

use App\Model\Chat\ChatRecord;
use App\Model\Chat\ChatRecordsFile;
use App\Model\EmoticonDetail;
use App\Model\Group\UsersGroup;
use App\Model\UsersEmoticon;

/**
 * 表情服务层
 *
 * Class EmoticonService
 * @package App\Services
 */
class EmoticonService extends BaseService
{
    /**
     * 安装系统表情包
     *
     * @param int $user_id 用户ID
     * @param int $emoticon_id 表情包ID
     * @return mixed
     */
    public function installSysEmoticon(int $user_id, int $emoticon_id)
    {
        $info = UsersEmoticon::select(['id', 'user_id', 'emoticon_ids'])->where('user_id', $user_id)->first();
        if (!$info) {
            return UsersEmoticon::create(['user_id' => $user_id, 'emoticon_ids' => $emoticon_id]) ? true : false;
        }

        $emoticon_ids = $info->emoticon_ids;
        if (in_array($emoticon_id, $emoticon_ids)) {
            return true;
        }

        $emoticon_ids[] = $emoticon_id;
        return UsersEmoticon::where('user_id', $user_id)->update([
            'emoticon_ids' => implode(',', $emoticon_ids)
        ]) ? true : false;
    }

    /**
     * 移除已安装的系统表情包
     *
     * @param int $user_id 用户ID
     * @param int $emoticon_id 表情包ID
     * @return bool
     */
    public function removeSysEmoticon(int $user_id, int $emoticon_id)
    {
        $info = UsersEmoticon::select(['id', 'user_id', 'emoticon_ids'])->where('user_id', $user_id)->first();
        if (!$info || !in_array($emoticon_id, $info->emoticon_ids)) {
            return false;
        }

        $emoticon_ids = $info->emoticon_ids;
        foreach ($emoticon_ids as $k => $id) {
            if ($id == $emoticon_id) {
                unset($emoticon_ids[$k]);
            }
        }

        if (count($info->emoticon_ids) == count($emoticon_ids)) {
            return false;
        }

        return UsersEmoticon::where('user_id', $user_id)->update([
            'emoticon_ids' => implode(',', $emoticon_ids)
        ]) ? true : false;
    }

    /**
     * 获取用户安装的表情ID
     *
     * @param int $user_id 用户ID
     * @return array
     */
    public function getInstallIds(int $user_id)
    {
        $result = UsersEmoticon::where('user_id', $user_id)->value('emoticon_ids');
        return $result ? array_filter($result) : [];
    }

    /**
     * 收藏聊天图片
     *
     * @param int $user_id 用户ID
     * @param int $record_id 聊天消息ID
     * @return array
     */
    public function collect(int $user_id, int $record_id)
    {
        $result = ChatRecord::where([
            ['id', '=', $record_id],
            ['msg_type', '=', 2],
            ['is_revoke', '=', 0],
        ])->first(['id', 'source', 'msg_type', 'user_id', 'receive_id', 'is_revoke']);

        if (!$result) {
            return [false, []];
        }

        if ($result->source == 1) {
            if ($result->user_id != $user_id && $result->receive_id != $user_id) {
                return [false, []];
            }
        } else {
            if (!UsersGroup::isMember($result->receive_id, $user_id)) {
                return [false, []];
            }
        }

        $fileInfo = ChatRecordsFile::where('record_id', $result->id)->where('file_type', 1)->first([
            'file_suffix',
            'file_size',
            'save_dir'
        ]);

        if (!$fileInfo) {
            return [false, []];
        }

        $result = EmoticonDetail::where('user_id', $user_id)->where('url', $fileInfo->save_dir)->first();
        if ($result) {
            return [false, []];
        }

        $res = EmoticonDetail::create([
            'user_id' => $user_id,
            'url' => $fileInfo->save_dir,
            'file_suffix' => $fileInfo->file_suffix,
            'file_size' => $fileInfo->file_size,
            'created_at' => time()
        ]);

        return $res ? [true, ['media_id' => $res->id, 'src' => get_media_url($res->url)]] : [false, []];
    }

    /**
     * 移除收藏的表情包
     *
     * @param int $user_id 用户ID
     * @param array $ids 表情包详情ID
     * @return
     */
    public function deleteCollect(int $user_id, array $ids)
    {
        return EmoticonDetail::whereIn('id', $ids)->where('user_id', $user_id)->delete();
    }

    /**
     * 获取表情包列表
     *
     * @param array $where
     * @return mixed
     */
    public function getDetailsAll(array $where = [])
    {
        $list = EmoticonDetail::where($where)->get(['id as media_id', 'url as src'])->toArray();

        foreach ($list as $k => $value) {
            $list[$k]['src'] = get_media_url($value['src']);
        }

        return $list;
    }
}
