<?php
/**
 * This is my open source code, please do not use it for commercial applications.
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author Yuandong<837215079@qq.com>
 * @link   https://github.com/gzydong/hyperf-chat
 */

namespace App\Controller\Api\V1;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use App\Model\User;
use App\Service\UserService;
use App\Service\SmsCodeService;

/**
 * 授权相关控制器
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
     * @var SmsCodeService
     */
    private $smsCodeService;

    /**
     * 授权登录接口
     * @RequestMapping(path="login", methods="post")
     */
    public function login()
    {
        $params = $this->request->inputs(['mobile', 'password', 'platform']);
        $this->validate($params, [
            'mobile'   => "required|regex:/^1[345789][0-9]{9}$/",
            'password' => 'required',
            'platform' => 'required|in:h5,ios,windows,mac,web',
        ]);

        $userInfo = $this->userService->login($params['mobile'], $params['password']);
        if (!$userInfo) {
            return $this->response->fail('账号不存在或密码填写错误！');
        }

        try {
            $token = auth('jwt')->login($userInfo);
        } catch (\Exception $exception) {
            return $this->response->error('登录异常，请稍后再试！');
        }

        return $this->response->success([
            'authorize' => [
                'access_token' => $token,
                'expires_in'   => auth('jwt')->getJwtManager()->getTtl()
            ],
            'user_info' => [
                'nickname' => $userInfo->nickname,
                'avatar'   => $userInfo->avatar,
                'gender'   => $userInfo->gender,
                'motto'    => $userInfo->motto,
                'email'    => $userInfo->email,
            ]
        ]);
    }

    /**
     * 退出登录接口
     * @RequestMapping(path="logout", methods="post")
     */
    public function logout()
    {
        auth('jwt')->check() && auth('jwt')->logout();

        return $this->response->success([], 'Successfully logged out');
    }

    /**
     * 账号注册接口
     * @RequestMapping(path="register", methods="post")
     */
    public function register()
    {
        $params = $this->request->all();
        $this->validate($params, [
            'nickname' => "required|max:20",
            'mobile'   => "required|regex:/^1[345789][0-9]{9}$/",
            'password' => 'required|max:16',
            'sms_code' => 'required|digits:6',
            'platform' => 'required|in:h5,ios,windows,mac,web',
        ]);

        if (!$this->smsCodeService->check('user_register', $params['mobile'], $params['sms_code'])) {
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
     * @RequestMapping(path="forget", methods="post")
     */
    public function forget()
    {
        $params = $this->request->inputs(['mobile', 'password', 'sms_code']);
        $this->validate($params, [
            'mobile'   => "required|regex:/^1[345789][0-9]{9}$/",
            'password' => 'required|max:16',
            'sms_code' => 'required|digits:6',
        ]);

        if (!$this->smsCodeService->check('forget_password', $params['mobile'], $params['sms_code'])) {
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
     * @RequestMapping(path="refresh", methods="post")
     */
    public function refresh()
    {
        if (auth('jwt')->guest()) {
            return $this->response->fail('登录 token 刷新失败！');
        }

        return $this->response->success([
            'authorize' => [
                'token'  => auth('jwt')->refresh(),
                'expire' => auth('jwt')->getJwtManager()->getTtl()
            ]
        ]);
    }

    /**
     * 发送验证码
     * @RequestMapping(path="send-verify-code", methods="post")
     */
    public function sendVerifyCode()
    {
        $params = $this->request->inputs(['type', 'mobile']);
        $this->validate($params, [
            'type'   => "required",
            'mobile' => "required|regex:/^1[345789][0-9]{9}$/"
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
