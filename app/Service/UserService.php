<?php

namespace App\Service;

use App\Model\User;
use App\Model\ArticleClass;

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

    /**
     * 账号注册逻辑
     *
     * @param array $data 用户数据
     * @return bool
     */
    public function register(array $data)
    {
        try {
            $data['password'] = Hash::make($data['password']);
            $data['created_at'] = date('Y-m-d H:i:s');
            $result = User::create($data);

            // 创建用户的默认笔记分类
            ArticleClass::create([
                'user_id' => $result->id,
                'class_name' => '我的笔记',
                'is_default' => 1,
                'sort' => 1,
                'created_at' => time()
            ]);
        } catch (Exception $e) {
            $result = false;
            DB::rollBack();
        }

        return $result ? true : false;
    }
}
