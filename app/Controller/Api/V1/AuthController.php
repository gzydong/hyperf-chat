<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Constant\SmsConstant;
use App\Event\LoginEvent;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use App\Service\UserService;
use App\Service\SmsCodeService;
use Psr\Http\Message\ResponseInterface;

/**
 * 授权相关控制器
 * @Controller(prefix="/api/v1/auth")
 */
class AuthController extends CController
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var SmsCodeService
     */
    private $sms;

    public function __construct(SmsCodeService $smsCodeService, UserService $userService)
    {
        parent::__construct();

        $this->userService = $userService;
        $this->sms         = $smsCodeService;
    }

    /**
     * 授权登录接口
     *
     * @RequestMapping(path="login", methods="post")
     */
    public function login(): ResponseInterface
    {
        $params = $this->request->inputs(['mobile', 'password', 'platform']);

        $this->validate($params, [
            'mobile'   => "required|phone",
            'password' => 'required',
            'platform' => 'required|in:h5,ios,windows,mac,web',
        ]);

        $user = $this->userService->login($params['mobile'], $params['password']);
        if (!$user) {
            return $this->response->fail('账号不存在或密码填写错误！');
        }

        try {
            $token = $this->guard()->login($user);
        } catch (\Exception $exception) {
            return $this->response->error('登录异常，请稍后再试！');
        }

        event()->dispatch(new LoginEvent($this->request, $user));

        return $this->response->success([
            'type'         => 'Bearer',
            'access_token' => $token,
            'expires_in'   => $this->guard()->getJwtManager()->getTtl(),
        ]);
    }

    /**
     * 退出登录接口
     *
     * @RequestMapping(path="logout", methods="post")
     */
    public function logout(): ResponseInterface
    {
        $this->guard()->check() && $this->guard()->logout();

        return $this->response->success();
    }

    /**
     * 账号注册接口
     *
     * @RequestMapping(path="register", methods="post")
     */
    public function register(): ResponseInterface
    {
        $params = $this->request->all();

        $this->validate($params, [
            'nickname' => "required|max:20",
            'mobile'   => "required|phone",
            'password' => 'required|max:16',
            'sms_code' => 'required|digits:6',
            'platform' => 'required|in:h5,ios,windows,mac,web',
        ]);

        if (!$this->sms->check(SmsConstant::SmsRegisterChannel, (string)$params['mobile'], (string)$params['sms_code'])) {
            return $this->response->fail('验证码填写错误！');
        }

        $isTrue = $this->userService->register([
            'mobile'   => $params['mobile'],
            'password' => $params['password'],
            'nickname' => strip_tags($params['nickname']),
        ]);

        if (!$isTrue) {
            return $this->response->fail('账号注册失败！');
        }

        $this->sms->delCode(SmsConstant::SmsRegisterChannel, $params['mobile']);

        return $this->response->success();
    }

    /**
     * 账号找回接口
     *
     * @RequestMapping(path="forget", methods="post")
     */
    public function forget(): ResponseInterface
    {
        $params = $this->request->inputs(['mobile', 'password', 'sms_code']);

        $this->validate($params, [
            'mobile'   => "required|phone",
            'password' => 'required|max:16',
            'sms_code' => 'required|digits:6',
        ]);

        if (!$this->sms->check(SmsConstant::SmsForgetAccountChannel, (string)$params['mobile'], (string)$params['sms_code'])) {
            return $this->response->fail('验证码填写错误！');
        }

        $isTrue = $this->userService->resetPassword($params['mobile'], $params['password']);
        if (!$isTrue) {
            return $this->response->fail('重置密码失败！');
        }

        $this->sms->delCode(SmsConstant::SmsForgetAccountChannel, $params['mobile']);

        return $this->response->success();
    }

    /**
     * 授权刷新接口
     *
     * @RequestMapping(path="refresh", methods="post")
     */
    public function refresh(): ResponseInterface
    {
        if ($this->guard()->guest()) {
            return $this->response->fail('token 刷新失败！');
        }

        return $this->response->success([
            'type'   => 'Bearer',
            'token'  => $this->guard()->refresh(),
            'expire' => $this->guard()->getJwtManager()->getTtl()
        ]);
    }
}
