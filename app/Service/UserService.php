<?php

namespace App\Service;

use App\Model\User;

class UserService extends BaseService
{
    /**
     * 登录逻辑
     *
     * @param string $mobile 手机号
     * @param string $password 登录密码
     * @return array|bool
     */
    public function login(string $mobile,string $password){
        $user = User::where('mobile',$mobile)->first();

        if(!$user){
            return false;
        }

        if(!password_verify($password,$user->password)){
            return false;
        }

        return $user->toArray();
    }
}
