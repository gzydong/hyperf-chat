<?php

declare (strict_types=1);

namespace App\Model;

/**
 * Class User
 *
 * @property integer $id 用户ID
 * @property string $nickname 用户昵称
 * @property string $mobile 登录手机号
 * @property string $password 登录密码
 * @property string $avatar 头像
 * @property integer $gender 性别
 * @property integer $created_at 注册时间
 *
 * @package App\Model
 */
class User extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'mobile', 'nickname', 'avatar', 'gender', 'password', 'motto', 'email', 'created_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];
}
