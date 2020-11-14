<?php

namespace App\Controller\Api\V1;

use App\Cache\ApplyNumCache;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Phper666\JWTAuth\Middleware\JWTAuthMiddleware;
use App\Constants\ResponseCode;
use App\Helper\Hash;
use App\Model\User;
use App\Model\UsersChatList;
use App\Model\UsersFriend;
use App\Service\SmsCodeService;
use App\Support\SendEmailCode;
use App\Service\FriendService;
use App\Service\UserService;
use App\Service\SocketFDService;

/**
 * Class UsersController
 *
 * @Controller(path="/api/v1/users")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class UsersController extends CController
{
    /**
     * @Inject
     * @var FriendService
     */
    protected $friendService;

    /**
     * @Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @inject
     * @var SocketFDService
     */
    private $socketFDService;

    /**
     * 获取我的好友列表
     *
     * @RequestMapping(path="friends", methods="get")
     */
    public function getUserFriends()
    {
        $rows = UsersFriend::getUserFriends($this->uid());

        $runArr = $this->socketFDService->getServerRunIdAll();
        foreach ($rows as $k => $row) {
            $rows[$k]['online'] = $this->socketFDService->isOnlineAll($row['id'], $runArr);
        }

        return $this->response->success($rows);
    }

    /**
     * 解除好友关系
     *
     * @RequestMapping(path="remove-friend", methods="post")
     */
    public function removeFriend()
    {
        $params = $this->request->all();
        $this->validate($params, [
            'friend_id' => 'required|integer'
        ]);

        $user_id = $this->uid();
        if (!$this->friendService->removeFriend($user_id, $params['friend_id'])) {
            return $this->response->fail('好友关系解除成功...');
        }

        //删除好友会话列表
        UsersChatList::delItem($user_id, $params['friend_id'], 2);
        UsersChatList::delItem($params['friend_id'], $user_id, 2);

        return $this->response->success([], '好友关系解除成功...');
    }

    /**
     * 获取用户群聊列表
     *
     * @RequestMapping(path="user-groups", methods="get")
     */
    public function getUserGroups()
    {
        return $this->response->success(
            $this->userService->getUserChatGroups($this->uid())
        );
    }

    /**
     * 获取我的信息
     *
     * @RequestMapping(path="detail", methods="get")
     */
    public function getUserDetail()
    {
        $userInfo = $this->userService->findById($this->uid(), ['mobile', 'nickname', 'avatar', 'motto', 'email', 'gender']);
        return $this->response->success([
            'mobile' => $userInfo->mobile,
            'nickname' => $userInfo->nickname,
            'avatar' => $userInfo->avatar,
            'motto' => $userInfo->motto,
            'email' => $userInfo->email,
            'gender' => $userInfo->gender
        ]);
    }

    /**
     * 用户相关设置
     *
     * @RequestMapping(path="setting", methods="get")
     */
    public function getUserSetting()
    {
        $userInfo = $this->userService->findById($this->uid(), ['id', 'nickname', 'avatar', 'motto', 'gender']);
        return $this->response->success([
            'user_info' => [
                'uid' => $userInfo->id,
                'nickname' => $userInfo->nickname,
                'avatar' => $userInfo->avatar,
                'motto' => $userInfo->motto,
                'gender' => $userInfo->gender,
            ],
            'setting' => [
                'theme_mode' => '',
                'theme_bag_img' => '',
                'theme_color' => '',
                'notify_cue_tone' => '',
                'keyboard_event_notify' => ''
            ]
        ]);
    }

    /**
     * 编辑我的信息
     *
     * @RequestMapping(path="edit-user-detail", methods="post")
     */
    public function editUserDetail()
    {
        $params = $this->request->inputs(['nickname', 'avatar', 'motto', 'gender']);
        $this->validate($params, [
            'nickname' => 'required',
            'motto' => 'present',
            'gender' => 'required|in:0,1,2',
            'avatar' => 'present|url'
        ]);

        $isTrue = User::where('id', $this->uid())->update($params);

        return $isTrue
            ? $this->response->success([], '个人信息修改成功...')
            : $this->response->fail('个人信息修改失败...');
    }

    /**
     * 修改用户头像
     *
     * @RequestMapping(path="edit-avatar", methods="post")
     */
    public function editAvatar()
    {
        $params = $this->request->inputs(['avatar']);
        $this->validate($params, [
            'avatar' => 'required|url'
        ]);

        $isTrue = User::where('id', $this->uid())->update(['avatar' => $params['avatar']]);
        return $isTrue
            ? $this->response->success([], '头像修改成功...')
            : $this->response->fail('头像修改失败...');
    }

    /**
     * 通过手机号查找用户
     *
     * @RequestMapping(path="search-user", methods="post")
     */
    public function searchUserInfo()
    {
        $params = $this->request->inputs(['user_id', 'mobile']);

        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $this->validate($params, ['user_id' => 'present|integer']);
            $where['uid'] = $params['user_id'];
        } else if (isset($params['mobile']) && !empty($params['mobile'])) {
            $this->validate($params, ['mobile' => "present|regex:/^1[345789][0-9]{9}$/"]);
            $where['mobile'] = $params['mobile'];
        } else {
            return $this->response->fail('请求参数不正确...', [], ResponseCode::VALIDATION_ERROR);
        }

        if ($data = $this->userService->searchUserInfo($where, $this->uid())) {
            return $this->response->success($data);
        }

        return $this->response->fail('查询失败...');
    }

    /**
     * 编辑好友备注信息
     *
     * @RequestMapping(path="edit-friend-remark", methods="post")
     */
    public function editFriendRemark()
    {
        $params = $this->request->inputs(['friend_id', 'remarks']);
        $this->validate($params, [
            'friend_id' => 'required|integer',
            'remarks' => "required",
        ]);

        $isTrue = $this->friendService->editFriendRemark($this->uid(), $params['friend_id'], $params['remarks']);
        return $isTrue
            ? $this->response->success([], '备注修改成功...')
            : $this->response->fail('备注修改失败...');
    }

    /**
     * 发送添加好友申请
     *
     * @RequestMapping(path="send-friend-apply", methods="post")
     */
    public function sendFriendApply()
    {
        $params = $this->request->inputs(['friend_id', 'remarks']);
        $this->validate($params, [
            'friend_id' => 'required|integer',
            'remarks' => 'present',
        ]);

        $user = $this->userService->findById($params['friend_id']);
        if (!$user) {
            return $this->response->fail('用户不存在...');
        }

        if (!$this->friendService->addFriendApply($this->uid(), $params['friend_id'], $params['remarks'])) {
            return $this->response->fail('发送好友申请失败...');
        }

        //判断对方是否在线。如果在线发送消息通知
        // ...

        return $this->response->success([], '发送好友申请成功...');
    }

    /**
     * 处理好友的申请
     *
     * @RequestMapping(path="handle-friend-apply", methods="post")
     */
    public function handleFriendApply()
    {
        $params = $this->request->inputs(['apply_id', 'remarks']);
        $this->validate($params, [
            'apply_id' => 'required|integer',
            'remarks' => 'present',
        ]);

        $isTrue = $this->friendService->handleFriendApply($this->uid(), $params['apply_id'], $params['remarks']);
        //判断是否是同意添加好友
        if ($isTrue) {
            //... 推送处理消息
        }

        return $isTrue
            ? $this->response->success([], '处理成功...')
            : $this->response->fail('处理失败...');
    }

    /**
     * 删除好友申请记录
     *
     * @RequestMapping(path="delete-friend-apply", methods="post")
     */
    public function deleteFriendApply()
    {
        $params = $this->request->inputs(['apply_id']);
        $this->validate($params, [
            'apply_id' => 'required|integer',
        ]);

        $isTrue = $this->friendService->delFriendApply($this->uid(), $params['apply_id']);
        return $isTrue
            ? $this->response->success([], '删除成功...')
            : $this->response->fail('删除失败...');
    }

    /**
     * 获取我的好友申请记录
     *
     * @RequestMapping(path="friend-apply-records", methods="get")
     */
    public function getFriendApplyRecords()
    {
        $params = $this->request->inputs(['page', 'page_size']);
        $this->validate($params, [
            'page' => 'present|integer',
            'page_size' => 'present|integer',
        ]);

        $page = $this->request->input('page', 1);
        $page_size = $this->request->input('page_size', 10);
        $user_id = $this->uid();

        $data = $this->friendService->findApplyRecords($user_id, $page, $page_size);
        return $this->response->success($data);
    }

    /**
     * 获取好友申请未读数
     *
     * @RequestMapping(path="friend-apply-num", methods="get")
     */
    public function getApplyUnreadNum()
    {
        $num = ApplyNumCache::get($this->uid());
        return $this->response->success([
            'unread_num' => $num ? $num : 0
        ]);
    }

    /**
     * 修改我的密码
     *
     * @RequestMapping(path="change-password", methods="post")
     */
    public function editUserPassword()
    {
        $params = $this->request->inputs(['old_password', 'new_password']);
        $this->validate($params, [
            'old_password' => 'required',
            'new_password' => 'required',
        ]);

        $userInfo = $this->userService->findById($this->uid(), ['id', 'password', 'mobile']);

        // 验证密码是否正确
        if (!Hash::check($this->request->post('old_password'), $userInfo->password)) {
            return $this->response->fail('旧密码验证失败...');
        }

        $isTrue = $this->userService->resetPassword($userInfo->mobile, $params['new_password']);
        return $isTrue
            ? $this->response->success([], '密码修改成功...')
            : $this->response->fail('密码修改失败...');
    }

    /**
     * 更换用户手机号
     *
     * @RequestMapping(path="change-mobile", methods="post")
     *
     * @param SmsCodeService $smsCodeService
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function editUserMobile(SmsCodeService $smsCodeService)
    {
        $params = $this->request->inputs(['mobile', 'password', 'sms_code']);
        $this->validate($params, [
            'mobile' => "required|regex:/^1[345789][0-9]{9}$/",
            'password' => 'required',
            'sms_code' => 'required|digits:6',
        ]);

        if (!$smsCodeService->check('change_mobile', $params['mobile'], $params['sms_code'])) {
            return $this->response->fail('验证码填写错误...');
        }

        $user_id = $this->uid();
        if (!Hash::check($params['password'], User::where('id', $user_id)->value('password'))) {
            return $this->response->fail('账号密码验证失败...');
        }

        [$isTrue,] = $this->userService->changeMobile($user_id, $params['mobile']);
        if (!$isTrue) {
            return $this->response->fail('手机号更换失败...');
        }

        // 清除缓存信息
        $smsCodeService->delCode('change_mobile', $params['mobile']);
        return $this->response->success([], '手机号更换成功...');
    }

    /**
     * 修改用户邮箱接口
     *
     * @RequestMapping(path="change-email", methods="post")
     */
    public function editUserEmail()
    {
        $params = $this->request->inputs(['email', 'password', 'email_code']);
        $this->validate($params, [
            'email' => 'required|email',
            'password' => 'required',
            'email_code' => 'required|digits:6',
        ]);

        $sendEmailCode = new SendEmailCode();
        if (!$sendEmailCode->check(SendEmailCode::CHANGE_EMAIL, $params['email'], $params['email_code'])) {
            return $this->response->fail('验证码填写错误...');
        }

        $uid = $this->uid();
        $user_password = User::where('id', $uid)->value('password');
        if (!Hash::check($params['password'], $user_password)) {
            return $this->response->fail('账号密码验证失败...');
        }

        $isTrue = User::where('id', $uid)->update(['email' => $params['email']]);
        if (!$isTrue) {
            return $this->response->fail('邮箱设置失败...');
        }

        $sendEmailCode->delCode(SendEmailCode::CHANGE_EMAIL, $params['email']);
        return $this->response->success([], '邮箱设置成功...');
    }

    /**
     * 修改手机号发送验证码
     *
     * @RequestMapping(path="send-mobile-code", methods="post")
     *
     * @param SmsCodeService $smsCodeService
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function sendMobileCode(SmsCodeService $smsCodeService)
    {
        $params = $this->request->inputs(['mobile']);
        $this->validate($params, [
            'mobile' => "present|regex:/^1[345789][0-9]{9}$/",
        ]);

        $user_id = $this->uid();
        if (in_array($user_id, [2054, 2055])) {
            return $this->response->fail('测试账号不支持修改手机号...');
        }

        if (User::where('mobile', $params['mobile'])->exists()) {
            return $this->response->fail('手机号已被他人注册...');
        }

        $data = ['is_debug' => true];
        [$isTrue, $result] = $smsCodeService->send('change_mobile', $params['mobile']);
        if ($isTrue) {
            $data['sms_code'] = $result['data']['code'];
        } else {
            // ... 处理发送失败逻辑，当前默认发送成功
        }

        return $this->response->success($data, '验证码发送成功...');
    }

    /**
     * 发送绑定邮箱的验证码
     *
     * @RequestMapping(path="send-change-email-code", methods="post")
     *
     * @param SendEmailCode $sendEmailCode
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function sendChangeEmailCode(SendEmailCode $sendEmailCode)
    {
        $params = $this->request->inputs(['email']);
        $this->validate($params, [
            'email' => "required|email",
        ]);

        $isTrue = $sendEmailCode->send(SendEmailCode::CHANGE_EMAIL, '绑定邮箱', $params['email']);
        if (!$isTrue) {
            return $this->response->fail('验证码发送失败...');
        }

        return $this->response->success([], '验证码发送成功...');
    }
}