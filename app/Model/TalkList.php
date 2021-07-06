<?php

declare (strict_types=1);

namespace App\Model;

/**
 * 聊天列表组数据表模型
 *
 * @property integer $id            聊天列表ID
 * @property integer $talk_type     聊天类型[1:好友;2:群聊;]
 * @property integer $user_id       用户ID或消息发送者ID
 * @property integer $receiver_id   接收者ID[好友ID或群ID]
 * @property integer $is_delete     是否删除
 * @property integer $is_top        是否置顶
 * @property integer $is_disturb    消息免打扰
 * @property integer $is_robot      是否机器人
 * @property string  $created_at    创建时间
 * @property string  $updated_at    更新时间
 * @package App\Model
 */
class TalkList extends BaseModel
{
    protected $table = 'talk_list';

    public $timestamps = true;

    protected $fillable = [
        'talk_type',
        'user_id',
        'receiver_id',
        'is_delete',
        'is_top',
        'is_robot',
        'is_disturb',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id'          => 'integer',
        'talk_type'   => 'integer',
        'user_id'     => 'integer',
        'receiver_id' => 'integer',
        'is_delete'   => 'integer',
        'is_top'      => 'integer',
        'is_robot'    => 'integer',
        'is_disturb'  => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime'
    ];

    /**
     * 创建聊天列表记录
     *
     * @param int $user_id     用户ID
     * @param int $receiver_id 接收者ID
     * @param int $talk_type   创建类型[1:私聊;2:群聊;]
     * @return array
     */
    public static function addItem(int $user_id, int $receiver_id, int $talk_type)
    {
        $result = self::query()->where([
            ['user_id', '=', $user_id],
            ['talk_type', '=', $talk_type],
            ['receiver_id', '=', $receiver_id],
        ])->first();

        if (!$result) {
            $result = self::query()->create([
                'talk_type'   => $talk_type,
                'user_id'     => $user_id,
                'receiver_id' => $receiver_id,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        $result->is_top     = 0;
        $result->is_delete  = 0;
        $result->is_disturb = 0;
        $result->updated_at = date('Y-m-d H:i:s');
        $result->save();

        return [
            'id'          => $result->id,
            'talk_type'   => $result->talk_type,
            'receiver_id' => $result->receiver_id,
        ];
    }

    /**
     * 聊天对话列表置顶操作
     *
     * @param int  $user_id 用户ID
     * @param int  $list_id 对话列表ID
     * @param bool $is_top  是否置顶（true:是 false:否）
     * @return bool
     */
    public static function topItem(int $user_id, int $list_id, $is_top = true)
    {
        return (bool)self::query()->where([
            ['id', '=', $list_id],
            ['user_id', '=', $user_id],
        ])->update([
            'is_top'     => $is_top ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 删除聊天列表
     *
     * @param int $user_id 用户ID
     * @param int $id      聊天列表ID、好友ID或群聊ID
     * @param int $type    ID类型[1:聊天列表ID;2:好友ID;3:群聊ID;]
     * @return bool
     */
    public static function delItem(int $user_id, int $id, $type = 1)
    {
        $model = self::query();
        if ($type == 1) {
            $model->where('id', $id)->where('user_id', $user_id);
        } else {
            $model->where([
                ['talk_type', '=', $type == 2 ? 1 : 2],
                ['user_id', '=', $user_id],
                ['receiver_id', '=', $id],
            ]);
        }

        return (bool)$model->update([
            'is_delete'  => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 设置消息免打扰
     *
     * @param int $user_id     用户ID
     * @param int $receiver_id 接收者ID
     * @param int $talk_type   接收者类型[1:好友;2:群组;]
     * @param int $is_disturb  是否免打扰
     * @return boolean
     */
    public static function setNotDisturb(int $user_id, int $receiver_id, int $talk_type, int $is_disturb)
    {
        $result = self::query()
            ->where([
                ['user_id', '=', $user_id],
                ['talk_type', '=', $talk_type],
                ['receiver_id', '=', $receiver_id],
            ])
            ->first(['id', 'is_disturb']);

        if (!$result || $is_disturb == $result->is_disturb) {
            return false;
        }

        return (bool)self::query()->where('id', $result->id)->update([
            'is_disturb' => $is_disturb,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}
