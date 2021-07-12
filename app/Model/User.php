<?php

declare (strict_types=1);

namespace App\Model;

use Qbhy\HyperfAuth\AuthAbility;
use Qbhy\HyperfAuth\Authenticatable;

/**
 * Class User
 *
 * @property integer $id         用户ID
 * @property string  $nickname   用户昵称
 * @property string  $mobile     登录手机号
 * @property string  $email      邮箱地址
 * @property string  $password   登录密码
 * @property string  $avatar     头像
 * @property integer $gender     性别
 * @property string  $motto      座右铭
 * @property string  $is_robot   是否机器人
 * @property string  $created_at 注册时间
 * @property string  $updated_at 更新时间
 * @package App\Model
 */
class User extends BaseModel implements Authenticatable
{
    use AuthAbility;

    protected $table = 'users';

    public $timestamps = true;

    protected $fillable = [
        'mobile',
        'nickname',
        'avatar',
        'gender',
        'password',
        'motto',
        'email',
        'is_robot',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'gender'   => 'integer',
        'is_robot' => 'integer',
    ];

    protected $hidden = [
        'password'
    ];
}
