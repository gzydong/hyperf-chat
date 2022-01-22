<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Constant\SmsConstant;
use App\Repository\UserRepository;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use App\Model\User;
use App\Support\SendEmailCode;
use App\Helper\HashHelper;
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
     * @var UserService
     */
    private $userService;

    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(UserService $userService, UserRepository $userRepository)
    {
        parent::__construct();

        $this->userService    = $userService;
        $this->userRepository = $userRepository;
    }

    /**
     * 获取我的信息
     * @RequestMapping(path="detail", methods="get")
     *
     * @return ResponseInterface
     */
    public function getDetail(): ResponseInterface
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
    public function getSetting(): ResponseInterface
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
     *
     * @RequestMapping(path="change/detail", methods="post")
     *
     * @return ResponseInterface
     */
    public function editDetail(): ResponseInterface
    {
        $params = $this->request->inputs(['nickname', 'avatar', 'motto', 'gender']);

        $this->validate($params, [
            'nickname' => 'required',
            'motto'    => 'present|max:100',
            'gender'   => 'required|in:0,1,2',
            'avatar'   => 'present|url'
        ]);

        $this->userRepository->update(["id" => $this->uid()], $params);

        return $this->response->success([], '个人信息修改成功...');
    }

    /**
     * 修改我的密码
     *
     * @RequestMapping(path="change/password", methods="post")
     *
     * @return ResponseInterface
     */
    public function editPassword(): ResponseInterface
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
     *
     * @RequestMapping(path="change/mobile", methods="post")
     *
     * @param SmsCodeService $smsCodeService
     * @return ResponseInterface
     */
    public function editMobile(SmsCodeService $smsCodeService): ResponseInterface
    {
        $params = $this->request->inputs(['mobile', 'password', 'sms_code']);

        $this->validate($params, [
            'mobile'   => "required|phone",
            'password' => 'required',
            'sms_code' => 'required|digits:6'
        ]);

        if (!$smsCodeService->check(SmsConstant::SmsChangeAccountChannel, $params['mobile'], (string)$params['sms_code'])) {
            return $this->response->fail('验证码填写错误！');
        }

        if (!HashHelper::check($params['password'], $this->user()->password)) {
            return $this->response->fail('账号密码验证失败！');
        }

        [$isTrue,] = $this->userService->changeMobile($this->uid(), $params['mobile']);
        if (!$isTrue) {
            return $this->response->fail('手机号更换失败！');
        }

        $smsCodeService->delCode(SmsConstant::SmsChangeAccountChannel, $params['mobile']);

        return $this->response->success([], '手机号更换成功...');
    }

    /**
     * 修改用户邮箱接口
     *
     * @RequestMapping(path="change/email", methods="post")
     *
     * @param \App\Support\SendEmailCode $emailCode
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function editEmail(SendEmailCode $emailCode): ResponseInterface
    {
        $params = $this->request->inputs(['email', 'password', 'email_code']);

        $this->validate($params, [
            'email'      => 'required|email',
            'password'   => 'required',
            'email_code' => 'required|digits:6'
        ]);

        if (!$emailCode->check(SendEmailCode::CHANGE_EMAIL, $params['email'], (string)$params['email_code'])) {
            return $this->response->fail('验证码填写错误！');
        }

        if (!HashHelper::check($params['password'], $this->user()->password)) {
            return $this->response->fail('账号密码验证失败！');
        }

        $isTrue = User::where('id', $this->uid())->update(['email' => $params['email']]);
        if (!$isTrue) {
            return $this->response->fail('邮箱设置失败！');
        }

        $emailCode->delCode(SendEmailCode::CHANGE_EMAIL, $params['email']);

        return $this->response->success([], '邮箱设置成功...');
    }
}
