<?php

namespace App\Service;

use App\Helper\Hash;
use App\Model\Group\UsersGroupMember;
use App\Model\User;
use App\Model\Article\ArticleClass;
use App\Model\UsersChatList;
use App\Model\UsersFriend;
use App\Model\UsersFriendsApply;
use Hyperf\DbConnection\Db;

class UserService extends BaseService
{
    /**
     * 获取用户信息
     *
     * @param int $user_id 用户ID
     * @param array $field 查询字段
     * @return mixed
     */
    public function findById(int $user_id, $field = ['*'])
    {
        return User::where('id', $user_id)->first($field);
    }

    /**
     * 登录逻辑
     *
     * @param string $mobile 手机号
     * @param string $password 登录密码
     * @return array|bool
     */
    public function login(string $mobile, string $password)
    {
        if (!$user = User::where('mobile', $mobile)->first()) {
            return false;
        }

        if (!password_verify($password, $user->password)) {
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
        Db::beginTransaction();
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

            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            $result = false;
        }

        return $result ? true : false;
    }

    /**
     * 账号重置密码
     *
     * @param string $mobile 用户手机好
     * @param string $password 新密码
     * @return mixed
     */
    public function resetPassword(string $mobile, string $password)
    {
        return User::where('mobile', $mobile)->update(['password' => Hash::make($password)]);
    }

    /**
     * 修改绑定的手机号
     *
     * @param int $user_id 用户ID
     * @param string $mobile 换绑手机号
     * @return array|bool
     */
    public function changeMobile(int $user_id, string $mobile)
    {
        if (User::where('mobile', $mobile)->value('id')) {
            return [false, '手机号已被他人绑定'];
        }

        $isTrue = (bool)User::where('id', $user_id)->update(['mobile' => $mobile]);
        return [$isTrue, null];
    }

    /**
     * 获取用户所在的群聊
     *
     * @param int $user_id 用户ID
     * @return mixed
     */
    public function getUserChatGroups(int $user_id)
    {
        $items = UsersGroupMember::select(['users_group.id', 'users_group.group_name', 'users_group.avatar', 'users_group.group_profile', 'users_group.user_id as group_user_id'])
            ->join('users_group', 'users_group.id', '=', 'users_group_member.group_id')
            ->where([
                ['users_group_member.user_id', '=', $user_id],
                ['users_group_member.status', '=', 0]
            ])
            ->orderBy('id', 'desc')->get()->toarray();

        foreach ($items as $key => $item) {
            // 判断当前用户是否是群主
            $items[$key]['isGroupLeader'] = $item['group_user_id'] == $user_id;

            //删除无关字段
            unset($items[$key]['group_user_id']);

            // 是否消息免打扰
            $items[$key]['not_disturb'] = UsersChatList::where([
                ['uid', '=', $user_id],
                ['type', '=', 2],
                ['group_id', '=', $item['id']]
            ])->value('not_disturb');
        }

        return $items;
    }

    /**
     * 通过手机号查找用户
     *
     * @param array $where 查询条件
     * @param int $user_id 当前登录用户的ID
     * @return array
     */
    public function searchUserInfo(array $where, int $user_id)
    {
        $info = User::select(['id', 'mobile', 'nickname', 'avatar', 'gender', 'motto']);
        if (isset($where['uid'])) {
            $info->where('id', $where['uid']);
        }

        if (isset($where['mobile'])) {
            $info->where('mobile', $where['mobile']);
        }

        $info = $info->first();
        $info = $info ? $info->toArray() : [];
        if ($info) {
            $info['friend_status'] = 0;//朋友关系状态  0:本人  1:陌生人 2:朋友
            $info['nickname_remark'] = '';
            $info['friend_apply'] = 0;

            // 判断查询信息是否是自己
            if ($info['id'] != $user_id) {
                $friend_id = $info['id'];

                $friendInfo = UsersFriend::select('id', 'user1', 'user2', 'active', 'user1_remark', 'user2_remark')->where(function ($query) use ($friend_id, $user_id) {
                    $query->where('user1', '=', $user_id)->where('user2', '=', $friend_id)->where('status', 1);
                })->orWhere(function ($query) use ($friend_id, $user_id) {
                    $query->where('user1', '=', $friend_id)->where('user2', '=', $user_id)->where('status', 1);
                })->first();

                $info['friend_status'] = $friendInfo ? 2 : 1;
                if ($friendInfo) {
                    $info['nickname_remark'] = ($friendInfo->user1 == $friend_id) ? $friendInfo->user2_remark : $friendInfo->user1_remark;
                } else {
                    $res = UsersFriendsApply::where('user_id', $user_id)->where('friend_id', $info['id'])->where('status', 0)->orderBy('id', 'desc')->exists();
                    $info['friend_apply'] = $res ? 1 : 0;
                }
            }
        }

        return $info;
    }
}
