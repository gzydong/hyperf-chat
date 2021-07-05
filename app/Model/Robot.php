<?php

declare (strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * Class Robot
 *
 * @property int    $id              机器人ID
 * @property int    $user_id         关联用户ID
 * @property int    $robot_name      机器人名称
 * @property int    $describe        描述信息
 * @property int    $logo            机器人logo
 * @property int    $is_talk         可发送消息
 * @property int    $status          状态
 * @property string $created_at      创建时间
 * @property string $updated_at      更新时间
 * @package App\Model
 */
class Robot extends Model
{
    protected $table = 'Robot';

    protected $fillable = [
        'user_id',
        'robot_name',
        'describe',
        'logo',
        'is_talk',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'user_id'    => 'integer',
        'is_talk'    => 'integer',
        'status'     => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
