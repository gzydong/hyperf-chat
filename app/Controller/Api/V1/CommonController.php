<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Repository\UserRepository;
use App\Support\SendEmailCode;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use App\Service\SmsCodeService;
use App\Constant\SmsConstant;

/**
 * class CommonController
 *
 * @Controller(prefix="/api/v1/common")
 */
class CommonController extends CController
{
    /**
     * @var SmsCodeService
     */
    private $sms;

    public function __construct(SmsCodeService $service)
    {
        parent::__construct();

        $this->sms = $service;
    }

    /**
     * 发送短信验证码
     *
     * @RequestMapping(path="sms-code", methods="post")
     */
    public function SmsCode(UserRepository $userRepository)
    {
        $params = $this->request->all();

        $this->validate($params, [
            'channel' => "required|in:login,register,forget_account,change_account",
            'mobile'  => "required|phone"
        ]);

        switch ($params['channel']) {
            case SmsConstant::SmsLoginChannel:
            case SmsConstant::SmsForgetAccountChannel:
                if (!$userRepository->isExistMobile($params['mobile'])) {
                    return $this->response->fail("账号不存在或密码错误！");
                }

                break;

            case SmsConstant::SmsRegisterChannel:
            case SmsConstant::SmsChangeAccountChannel:
                if ($userRepository->isExistMobile($params['mobile'])) {
                    return $this->response->fail("账号已被他（她）人使用！");
                }

                break;
            default:
                return $this->response->fail("发送异常！");
        }

        [$isTrue, $result] = $this->sms->send($params['channel'], $params['mobile']);
        if (!$isTrue) {
            return $this->response->fail($result['msg']);
        }

        // 可自行去掉
        $data             = [];
        $data['is_debug'] = true;
        $data['sms_code'] = $result['data']['code'];

        return $this->response->success($data);
    }

    /**
     * 发送邮件验证码
     *
     * @RequestMapping(path="email-code", methods="post")
     */
    public function EmailCode(SendEmailCode $sendEmailCode)
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

    /**
     * 公共设置
     *
     * @RequestMapping(path="setting", methods="post")
     */
    public function Setting()
    {

    }
}
