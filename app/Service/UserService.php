<?php
declare(strict_types=1);

namespace App\Service;

use App\Helper\HashHelper;
use App\Model\Contact\ContactApply;
use App\Model\User;
use App\Repository\Article\ArticleClassRepository;
use App\Repository\UserRepository;
use Hyperf\DbConnection\Db;

class UserService extends BaseService
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var ArticleClassRepository
     */
    private $articleClassRepository;

    public function __construct(UserRepository $userRepository, ArticleClassRepository $articleClassRepository)
    {
        $this->userRepository         = $userRepository;
        $this->articleClassRepository = $articleClassRepository;
    }

    /**
     * 登录逻辑
     *
     * @param string $mobile   手机号
     * @param string $password 登录密码
     *
     * @return \App\Model\User|false
     */
    public function login(string $mobile, string $password)
    {
        $user = $this->userRepository->first(["mobile" => $mobile]);
        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user->password)) {
            return false;
        }

        /** @var \App\Model\User $user */
        return $user;
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
            $data['password'] = HashHelper::make($data['password']);

            $result = $this->userRepository->create($data);

            $this->articleClassRepository->create([
                'user_id'    => $result->id,
                'class_name' => '我的笔记',
                'is_default' => 1,
                'sort'       => 1,
            ]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            return false;
        }

        return true;
    }

    /**
     * 账号重置密码
     *
     * @param string $mobile   用户手机号
     * @param string $password 新密码
     * @return bool
     */
    public function resetPassword(string $mobile, string $password): bool
    {
        return (bool)$this->userRepository->update(["mobile" => $mobile], [
            'password' => HashHelper::make($password)
        ]);
    }

    /**
     * 修改绑定的手机号
     *
     * @param int    $user_id 用户ID
     * @param string $mobile  换绑手机号
     * @return array
     */
    public function changeMobile(int $user_id, string $mobile)
    {
        if ($this->userRepository->isExistMobile($mobile)) {
            return [false, '手机号已被他人绑定'];
        }

        $this->userRepository->update(["id" => $user_id], [
            'mobile' => $mobile
        ]);

        return [true, null];
    }

    /**
     * 通过手机号查找用户
     *
     * @param int $friend_id  用户ID
     * @param int $me_user_id 当前登录用户的ID
     * @return array
     */
    public function getUserCard(int $friend_id, int $me_user_id): array
    {
        $info = $this->userRepository->find($friend_id, [
            'id', 'mobile', 'nickname', 'avatar', 'gender', 'motto'
        ]);

        if (!$info) return [];

        $info                    = $info->toArray();
        $info['friend_status']   = 0;//朋友关系[0:本人;1:陌生人;2:朋友;]
        $info['nickname_remark'] = '';
        $info['friend_apply']    = 0;

        // 判断查询信息是否是自己
        if ($friend_id != $me_user_id) {
            $is_friend = di()->get(UserFriendService::class)->isFriend($me_user_id, $friend_id, true);

            $info['friend_status'] = $is_friend ? 2 : 1;
            if ($is_friend) {
                $info['nickname_remark'] = di()->get(UserFriendService::class)->getFriendRemark($me_user_id, $friend_id);
            } else {
                $res = ContactApply::where('user_id', $me_user_id)
                    ->where('friend_id', $friend_id)
                    ->orderBy('id', 'desc')
                    ->exists();

                $info['friend_apply'] = $res ? 1 : 0;
            }
        }

        return $info;
    }
}
