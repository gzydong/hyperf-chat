<?php
declare(strict_types=1);

namespace App\Support;

use App\Template\MailerTemplate;

class SendEmailCode
{
    const FORGET_PASSWORD = 'forget_password';
    const CHANGE_MOBILE   = 'change_mobile';
    const CHANGE_REGISTER = 'user_register';
    const CHANGE_EMAIL    = 'change_email';

    /**
     * 获取缓存key
     *
     * @param string $type
     * @param string $mobile
     * @return string
     */
    private function getKey(string $type, string $mobile): string
    {
        return "email_code:{$type}:{$mobile}";
    }

    /**
     * 检测验证码是否正确
     *
     * @param string $type  发送类型
     * @param string $email 手机号
     * @param string $code  验证码
     * @return bool
     */
    public function check(string $type, string $email, string $code): bool
    {
        $sms_code = redis()->get($this->getKey($type, $email));
        if (!$sms_code) {
            return false;
        }

        return $sms_code == $code;
    }

    /**
     * 发送邮件验证码
     *
     * @param string $type  类型
     * @param string $title 邮件标题
     * @param string $email 邮箱地址
     * @return bool
     */
    public function send(string $type, string $title, string $email): bool
    {
        $key = $this->getKey($type, $email);
        if (!$sms_code = $this->getCode($key)) {
            $sms_code = (string)mt_rand(100000, 999999);
        }

        $this->setCode($key, $sms_code);

        // ...执行发送(后期使用队列)
        email()->send(
            $email, 'Lumen IM(绑定邮箱验证码)',
            di()->get(MailerTemplate::class)->emailCode($sms_code)
        );

        return true;
    }

    /**
     * 获取缓存的验证码
     *
     * @param string $key
     * @return mixed
     */
    public function getCode(string $key)
    {
        return redis()->get($key);
    }

    /**
     * 设置验证码缓存
     *
     * @param string    $key      缓存key
     * @param string    $sms_code 验证码
     * @param float|int $exp      过期时间
     * @return bool
     */
    public function setCode(string $key, string $sms_code, $exp = 60 * 15): bool
    {
        return redis()->setex($key, $exp, $sms_code);
    }

    /**
     * 删除验证码缓存
     *
     * @param string $type  类型
     * @param string $email 邮箱地址
     * @return int
     */
    public function delCode(string $type, string $email): int
    {
        return redis()->del($this->getKey($type, $email));
    }
}
