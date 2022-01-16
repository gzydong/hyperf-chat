<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Event\LoginEvent;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use App\Model\User;
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
     * @Inject
     * @var UserService
     */
    private $userService;

    /**
     * @Inject
     * @var SmsCodeService
     */
    private $smsCodeService;

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
        ], '账号登录成功...');
    }

    /**
     * 退出登录接口
     *
     * @RequestMapping(path="logout", methods="post")
     */
    public function logout(): ResponseInterface
    {
        $this->guard()->check() && $this->guard()->logout();

        return $this->response->success([], '退出登录成功...');
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

        if (!$this->smsCodeService->check('user_register', (string)$params['mobile'], (string)$params['sms_code'])) {
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

        // 删除验证码缓存
        $this->smsCodeService->delCode('user_register', $params['mobile']);

        return $this->response->success([], '账号注册成功...');
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

        if (!$this->smsCodeService->check('forget_password', (string)$params['mobile'], (string)$params['sms_code'])) {
            return $this->response->fail('验证码填写错误！');
        }

        $isTrue = $this->userService->resetPassword($params['mobile'], $params['password']);
        if (!$isTrue) {
            return $this->response->fail('重置密码失败！');
        }

        // 删除验证码缓存
        $this->smsCodeService->delCode('forget_password', $params['mobile']);

        return $this->response->success([], '账号注册成功...');
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
            'authorize' => [
                'type'   => 'Bearer',
                'token'  => $this->guard()->refresh(),
                'expire' => $this->guard()->getJwtManager()->getTtl()
            ]
        ]);
    }

    /**
     * 发送验证码
     *
     * @RequestMapping(path="send-verify-code", methods="post")
     */
    public function sendVerifyCode(): ResponseInterface
    {
        $params = $this->request->inputs(['type', 'mobile']);
        $this->validate($params, [
            'type'   => "required",
            'mobile' => "required|phone"
        ]);

        if (!$this->smsCodeService->isUsages($params['type'])) {
            return $this->response->fail('验证码发送失败！');
        }

        if ($params['type'] == 'forget_password') {
            if (!User::where('mobile', $params['mobile'])->value('id')) {
                return $this->response->fail('手机号未被注册使用！');
            }
        } else if ($params['type'] == 'change_mobile' || $params['type'] == 'user_register') {
            if (User::where('mobile', $params['mobile'])->value('id')) {
                return $this->response->fail('手机号已被他(她)人注册！');
            }
        }

        $data = ['is_debug' => true];
        [$isTrue, $result] = $this->smsCodeService->send($params['type'], $params['mobile']);
        if (!$isTrue) {
            // ... 处理发送失败逻辑，当前默认发送成功
            return $this->response->fail('验证码发送失败！');
        }

        // 测试环境下直接返回验证码
        $data['sms_code'] = $result['data']['code'];

        return $this->response->success($data, '验证码发送成功...');
    }
}
