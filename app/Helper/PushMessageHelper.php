<?php

namespace App\Helper;

use App\Model\User;

/**
 * Socket 资源处理
 * Class PushMessageHelper
 * @package App\Helpers
 */
class PushMessageHelper
{
    /**
     * 格式化对话的消息体
     *
     * @param array $data 对话的消息
     * @return array
     */
    public static function formatTalkMsg(array $data)
    {
        // 缓存优化
        if (!isset($data['nickname']) || !isset($data['avatar']) || empty($data['nickname']) || empty($data['avatar'])) {
            if (isset($data['user_id']) && !empty($data['user_id'])) {
                $info = User::where('id', $data['user_id'])->first(['nickname', 'avatar']);
                if ($info) {
                    $data['nickname'] = $info->nickname;
                    $data['avatar'] = $info->avatar;
                }
            }
        }

        $arr = [
            "id" => 0,
            "source" => 1,
            "msg_type" => 1,
            "user_id" => 0,
            "receive_id" => 0,
            "content" => '',
            "is_revoke" => 0,

            // 发送消息人的信息
            "nickname" => "",
            "avatar" => "",

            // 不同的消息类型
            "file" => [],
            "code_block" => [],
            "forward" => [],
            "invite" => [],

            "created_at" => "",
        ];

        return array_merge($arr, array_intersect_key($data, $arr));
    }
}
