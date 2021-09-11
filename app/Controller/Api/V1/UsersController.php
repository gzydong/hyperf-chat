<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use App\Model\User;
use App\Support\SendEmailCode;
use App\Helpers\HashHelper;
use App\Service\UserService;
use App\Service\SmsCodeService;
use Psr\Http\Message\ResponseInterface;

/**
 * Class UsersController
 * @Controller(prefix="/api/v1/users")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class UsersController extends CController
{
    /**
     * @Inject
     * @var UserService
     */
    private $userService;

    /**
     * 获取我的信息
     * @RequestMapping(path="detail", methods="get")
     *
     * @return ResponseInterface
     */
    public function getUserDetail(): ResponseInterface
    {
        $userInfo = $this->user();

        return $this->response->success([
            'mobile'   => $userInfo->mobile,
            'nickname' => $userInfo->nickname,
            'avatar'   => $userInfo->avatar,
            'motto'    => $userInfo->motto,
            'email'    => $userInfo->email,
            'gender'   => $userInfo->gender,
        ]);
    }

    /**
     * 用户相关设置
     * @RequestMapping(path="setting", methods="get")
     *
     * @return ResponseInterface
     */
    public function getUserSetting(): ResponseInterface
    {
        $userInfo = $this->user();

        return $this->response->success([
            'user_info' => [
                'uid'      => $userInfo->id,
                'nickname' => $userInfo->nickname,
                'avatar'   => $userInfo->avatar,
                'motto'    => $userInfo->motto,
                'gender'   => $userInfo->gender,
            ],
            'setting'   => [
                'theme_mode'            => '',
                'theme_bag_img'         => '',
                'theme_color'           => '',
                'notify_cue_tone'       => '',
                'keyboard_event_notify' => ''
            ]
        ]);
    }

    /**
     * 编辑我的信息
     * @RequestMapping(path="edit-user-detail", methods="post")
     *
     * @return ResponseInterface
     */
    public function editUserDetail(): ResponseInterface
    {
        $params = $this->request->inputs(['nickname', 'avatar', 'motto', 'gender']);
        $this->validate($params, [
            'nickname' => 'required',
            'motto'    => 'present|max:100',
            'gender'   => 'required|in:0,1,2',
            'avatar'   => 'present|url'
        ]);

        $params['updated_at'] = date("Y-m-d H:i:s");

        User::where('id', $this->uid())->update($params);

        return $this->response->success([], '个人信息修改成功...');
    }

    /**
     * 修改用户头像
     * @RequestMapping(path="edit-avatar", methods="post")
     *
     * @return ResponseInterface
     */
    public function editAvatar(): ResponseInterface
    {
        $params = $this->request->inputs(['avatar']);
        $this->validate($params, [
            'avatar' => 'required|url'
        ]);

        User::where('id', $this->uid())->update([
            'avatar'     => $params['avatar'],
            'updated_at' => date("Y-m-d H:i:s")
        ]);

        return $this->response->success([], '头像修改成功...');
    }

    /**
     * 通过用户ID查找用户
     * @RequestMapping(path="search-user", methods="post")
     *
     * @return ResponseInterface
     */
    public function search(): ResponseInterface
    {
        $params = $this->request->inputs(['user_id']);
        $this->validate($params, ['user_id' => 'required|integer']);

        if ($data = $this->userService->getUserCard($params['user_id'], $this->uid())) {
            return $this->response->success($data);
        }

        return $this->response->fail('用户查询失败！');
    }

    /**
     * 修改我的密码
     * @RequestMapping(path="change-password", methods="post")
     *
     * @return ResponseInterface
     */
    public function editUserPassword(): ResponseInterface
    {
        $params = $this->request->inputs(['old_password', 'new_password']);
        $this->validate($params, [
            'old_password' => 'required',
            'new_password' => 'required|min:6|max:16'
        ]);

        $userInfo = $this->user();

        // 验证密码是否正确
        if (!HashHelper::check($params['old_password'], $userInfo->password)) {
            return $this->response->fail('旧密码验证失败！');
        }

        $isTrue = $this->userService->resetPassword($userInfo->mobile, $params['new_password']);
        if (!$isTrue) {
            return $this->response->fail('密码修改失败！');
        }

        return $this->response->success([], '密码修改成功...');
    }

    /**
     * 更换用户手机号
     * @RequestMapping(path="change-mobile", methods="post")
     *
     * @param SmsCodeService $smsCodeService
     * @return ResponseInterface
     */
    public function editUserMobile(SmsCodeService $smsCodeService): ResponseInterface
    {
        $params = $this->request->inputs(['mobile', 'password', 'sms_code']);
        $this->validate($params, [
            'mobile'   => "required|phone",
            'password' => 'required',
            'sms_code' => 'required|digits:6'
        ]);

        if (!$smsCodeService->check('change_mobile', $params['mobile'], (string)$params['sms_code'])) {
            return $this->response->fail('验证码填写错误！');
        }

        if (!HashHelper::check($params['password'], $this->user()->password)) {
            return $this->response->fail('账号密码验证失败！');
        }

        [$isTrue,] = $this->userService->changeMobile($this->uid(), $params['mobile']);
        if (!$isTrue) {
            return $this->response->fail('手机号更换失败！');
        }

        // 清除缓存信息
        $smsCodeService->delCode('change_mobile', $params['mobile']);

        return $this->response->success([], '手机号更换成功...');
    }

    /**
     * 修改用户邮箱接口
     * @RequestMapping(path="change-email", methods="post")
     *
     * @return ResponseInterface
     */
    public function editUserEmail(): ResponseInterface
    {
        $params = $this->request->inputs(['email', 'password', 'email_code']);
        $this->validate($params, [
            'email'      => 'required|email',
            'password'   => 'required',
            'email_code' => 'required|digits:6'
        ]);

        $sendEmailCode = new SendEmailCode();
        if (!$sendEmailCode->check(SendEmailCode::CHANGE_EMAIL, $params['email'], (string)$params['email_code'])) {
            return $this->response->fail('验证码填写错误！');
        }

        if (!HashHelper::check($params['password'], $this->user()->password)) {
            return $this->response->fail('账号密码验证失败！');
        }

        $isTrue = User::where('id', $this->uid())->update(['email' => $params['email']]);
        if (!$isTrue) {
            return $this->response->fail('邮箱设置失败！');
        }

        $sendEmailCode->delCode(SendEmailCode::CHANGE_EMAIL, $params['email']);

        return $this->response->success([], '邮箱设置成功...');
    }

    /**
     * 修改手机号发送验证码
     * @RequestMapping(path="send-mobile-code", methods="post")
     *
     * @param SmsCodeService $smsCodeService
     * @return ResponseInterface
     */
    public function sendMobileCode(SmsCodeService $smsCodeService): ResponseInterface
    {
        $params = $this->request->inputs(['mobile']);
        $this->validate($params, [
            'mobile' => "present|phone"
        ]);

        $user_id = $this->uid();
        if (in_array($user_id, [2054, 2055])) {
            return $this->response->fail('测试账号不支持修改手机号！');
        }

        if (User::where('mobile', $params['mobile'])->exists()) {
            return $this->response->fail('手机号已被他人注册！');
        }

        $data = ['is_debug' => true];
        [$isTrue, $result] = $smsCodeService->send('change_mobile', $params['mobile']);
        if (!$isTrue) {
            // ... 处理发送失败逻辑，当前默认发送成功
            return $this->response->fail('验证码发送失败！');
        }

        // 测试环境下直接返回验证码
        $data['sms_code'] = $result['data']['code'];

        return $this->response->success($data, '验证码发送成功...');
    }

    /**
     * 发送绑定邮箱的验证码
     * @RequestMapping(path="send-change-email-code", methods="post")
     *
     * @param SendEmailCode $sendEmailCode
     * @return ResponseInterface
     */
    public function sendChangeEmailCode(SendEmailCode $sendEmailCode): ResponseInterface
    {
        $params = $this->request->inputs(['email']);
        $this->validate($params, [
            'email' => "required|email"
        ]);

        $isTrue = $sendEmailCode->send(SendEmailCode::CHANGE_EMAIL, '绑定邮箱', $params['email']);
        if (!$isTrue) {
            return $this->response->fail('验证码发送失败！');
        }

        return $this->response->success([], '验证码发送成功...');
    }
}
