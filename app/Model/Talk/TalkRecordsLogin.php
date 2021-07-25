<?php
declare(strict_types=1);

namespace App\Model\Talk;

use App\Model\BaseModel;

/**
 * Class TalkRecordsLogin
 *
 * @property int $record_id   消息记录ID
 * @property int $user_id     用户ID
 * @property int $ip          登录IP
 * @property int $platform    登录平台
 * @property int $agent       设备信息
 * @property int $address     登录地址
 * @property int $reason      异常信息
 * @property int $created_at  登录时间
 * @package App\Model\Talk
 */
class TalkRecordsLogin extends BaseModel
{
    protected $table = 'talk_records_login';

    protected $fillable = [
        'record_id',
        'user_id',
        'ip',
        'platform',
        'agent',
        'address',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'record_id' => 'integer',
        'user_id'   => 'integer',
    ];
}
