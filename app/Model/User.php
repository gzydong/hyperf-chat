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
 * @property integer $created_at 注册时间
 * @package App\Model
 */
class User extends BaseModel implements Authenticatable
{
    use AuthAbility;

    protected $table = 'users';

    protected $fillable = [
        'mobile',
        'nickname',
        'avatar',
        'gender',
        'password',
        'motto',
        'email',
        'created_at'
    ];

    protected $casts = [];

    protected $hidden = [
        'password'
    ];
}
