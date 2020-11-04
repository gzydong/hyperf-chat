<?php

namespace App\Controller\Api\V1;

use App\Constants\ResponseCode;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Service\UserService;
use App\Service\SmsCodeService;
use Phper666\JWTAuth\JWT;
use Phper666\JWTAuth\Middleware\JWTAuthMiddleware;

/**
 * 授权相关控制器
 *
 * @Controller(path="/api/v1/auth")
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
     * @var JWT
     */
    private $jwt;

    /**
     * 授权登录接口
     *
     * @RequestMapping(path="login", methods="post")
     *
     * @param JWT $jwt
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function login()
    {
        $this->validate($this->request->all(), [
            'mobile' => "required|regex:/^1[345789][0-9]{9}$/",
            'password' => 'required',
            'platform' => 'required|in:h5,ios,windows,mac',
        ], [
            'mobile.regex' => 'mobile 格式不正确'
        ]);

        $userInfo = $this->userService->login(
            $this->request->input('mobile'),
            $this->request->input('password')
        );
        if (!$userInfo) {
            return $this->response->fail('账号不存在或密码填写错误...', ResponseCode::FAIL);
        }

        try {
            $token = $this->jwt->getToken([
                'user_id' => $userInfo['id'],
                'platform' => $this->request->input('platform'),
            ]);
        } catch (\Exception $exception) {
            return $this->response->error('登录异常，请稍后再试...');
        }

        return $this->response->success([
            'authorize' => [
                'token' => $token,
                'expire' => $this->jwt->getTTL()
            ],
            'user_info' => [
                'nickname' => $userInfo['nickname'],
                'avatar' => $userInfo['avatar'],
                'gender' => $userInfo['gender'],
                'motto' => $userInfo['motto'],
                'email' => $userInfo['email'],
            ]
        ], '登录成功...');
    }

    /**
     * 退出登录接口
     *
     * @RequestMapping(path="logout", methods="post")
     * @Middleware(JWTAuthMiddleware::class)
     */
    public function logout()
    {
        $this->jwt->logout();

        return $this->response->success([], 'Successfully logged out');
    }

    /**
     * 账号注册接口
     *
     * @RequestMapping(path="register", methods="post")
     *
     * @param SmsCodeService $smsCodeService
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function register(SmsCodeService $smsCodeService)
    {
        $params = $this->request->all();
        $this->validate($params, [
            'nickname' => "required",
            'mobile' => "required|regex:/^1[345789][0-9]{9}$/",
            'password' => 'required',
            'sms_code' => 'required|integer|max:999999',
            'platform' => 'required|in:h5,ios,windows,mac',
        ]);

        if (!$smsCodeService->check('user_register', $params['mobile'], $params['sms_code'])) {
            return $this->response->fail('验证码填写错误...');
        }

        $isTrue = $this->userService->register([
            'mobile' => $params['mobile'],
            'password' => $params['password'],
            'nickname' => strip_tags($params['nickname']),
        ]);

        if ($isTrue) {
            $smsCodeService->delCode('user_register', $params['mobile']);
        }

        return $this->response->success([], 'Successfully logged out');
    }

    /**
     * 账号找回接口
     *
     * @RequestMapping(path="forget", methods="post")
     */
    public function forget()
    {

    }

    /**
     * 授权刷新接口
     *
     * @RequestMapping(path="refresh", methods="post")
     * @Middleware(JWTAuthMiddleware::class)
     */
    public function refresh()
    {
        return $this->response->success([
            'authorize' => [
                'token' => $this->jwt->refreshToken(),
                'expire' => $this->jwt->getTTL()
            ]
        ], '刷新 Token 成功...');
    }
}
