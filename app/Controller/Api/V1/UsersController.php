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
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use App\Model\User;
use App\Support\SendEmailCode;
use App\Helper\Hash;
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
    public function getUserDetail()
    {
        $userInfo = $this->userService->findById($this->uid(), ['mobile', 'nickname', 'avatar', 'motto', 'email', 'gender']);

        return $this->response->success([
            'mobile'   => $userInfo->mobile,
            'nickname' => $userInfo->nickname,
            'avatar'   => $userInfo->avatar,
            'motto'    => $userInfo->motto,
            'email'    => $userInfo->email,
            'gender'   => $userInfo->gender
        ]);
    }

    /**
     * 用户相关设置
     * @RequestMapping(path="setting", methods="get")
     *
     * @return ResponseInterface
     */
    public function getUserSetting()
    {
        $userInfo = $this->userService->findById($this->uid(), ['id', 'nickname', 'avatar', 'motto', 'gender']);

        return $this->response->success([
            'user_info' => [
                'uid'      => $userInfo->id,
                'nickname' => $userInfo->nickname,
                'avatar'   => $userInfo->avatar,
                'motto'    => $userInfo->motto,
                'gender'   => $userInfo->gender
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
    public function editUserDetail()
    {
        $params = $this->request->inputs(['nickname', 'avatar', 'motto', 'gender']);
        $this->validate($params, [
            'nickname' => 'required',
            'motto'    => 'present|max:100',
            'gender'   => 'required|in:0,1,2',
            'avatar'   => 'present|url'
        ]);

        $isTrue = User::where('id', $this->uid())->update($params);

        return $isTrue
            ? $this->response->success([], '个人信息修改成功...')
            : $this->response->fail('个人信息修改失败！');
    }

    /**
     * 修改用户头像
     * @RequestMapping(path="edit-avatar", methods="post")
     *
     * @return ResponseInterface
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
            : $this->response->fail('头像修改失败！');
    }

    /**
     * 通过手机号查找用户
     * @RequestMapping(path="search-user", methods="post")
     *
     * @return ResponseInterface
     */
    public function searchUserInfo()
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
    public function editUserPassword()
    {
        $params = $this->request->inputs(['old_password', 'new_password']);
        $this->validate($params, [
            'old_password' => 'required',
            'new_password' => 'required|min:6|max:16'
        ]);

        $userInfo = $this->userService->findById($this->uid(), ['id', 'password', 'mobile']);

        // 验证密码是否正确
        if (!Hash::check($this->request->post('old_password'), $userInfo->password)) {
            return $this->response->fail('旧密码验证失败！');
        }

        $isTrue = $this->userService->resetPassword($userInfo->mobile, $params['new_password']);
        return $isTrue
            ? $this->response->success([], '密码修改成功...')
            : $this->response->fail('密码修改失败！');
    }

    /**
     * 更换用户手机号
     * @RequestMapping(path="change-mobile", methods="post")
     *
     * @param SmsCodeService $smsCodeService
     * @return ResponseInterface
     */
    public function editUserMobile(SmsCodeService $smsCodeService)
    {
        $params = $this->request->inputs(['mobile', 'password', 'sms_code']);
        $this->validate($params, [
            'mobile'   => "required|phone",
            'password' => 'required',
            'sms_code' => 'required|digits:6'
        ]);

        if (!$smsCodeService->check('change_mobile', $params['mobile'], $params['sms_code'])) {
            return $this->response->fail('验证码填写错误！');
        }

        $user_id = $this->uid();
        if (!Hash::check($params['password'], User::where('id', $user_id)->value('password'))) {
            return $this->response->fail('账号密码验证失败！');
        }

        [$isTrue,] = $this->userService->changeMobile($user_id, $params['mobile']);
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
    public function editUserEmail()
    {
        $params = $this->request->inputs(['email', 'password', 'email_code']);
        $this->validate($params, [
            'email'      => 'required|email',
            'password'   => 'required',
            'email_code' => 'required|digits:6'
        ]);

        $sendEmailCode = new SendEmailCode();
        if (!$sendEmailCode->check(SendEmailCode::CHANGE_EMAIL, $params['email'], $params['email_code'])) {
            return $this->response->fail('验证码填写错误！');
        }

        $uid           = $this->uid();
        $user_password = User::where('id', $uid)->value('password');
        if (!Hash::check($params['password'], $user_password)) {
            return $this->response->fail('账号密码验证失败！');
        }

        $isTrue = User::where('id', $uid)->update(['email' => $params['email']]);
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
    public function sendMobileCode(SmsCodeService $smsCodeService)
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
    public function sendChangeEmailCode(SendEmailCode $sendEmailCode)
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
