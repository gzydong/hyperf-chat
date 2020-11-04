<?php

namespace App\Service;

/**
 * 短信发送服务
 *
 * Class SmsCodeService
 * @package App\Services
 */
class SmsCodeService
{
    /**
     * 短信验证码用途渠道
     */
    const SMS_USAGE = [
        'user_register',     // 注册账号
        'forget_password',   // 找回密码验
        'change_mobile',     // 修改手机
    ];

    /**
     * 获取Redis连接
     *
     * @return mixed|\Redis
     */
    private function redis()
    {
        return redis();
    }

    /**
     * 获取缓存key
     *
     * @param string $type 短信用途
     * @param string $mobile 手机号
     * @return string
     */
    private function getKey(string $type, string $mobile)
    {
        return "sms_code:{$type}:{$mobile}";
    }

    /**
     * 检测验证码是否正确
     *
     * @param string $type 发送类型
     * @param string $mobile 手机号
     * @param string $code 验证码
     * @return bool
     */
    public function check(string $type, string $mobile, string $code)
    {
        $sms_code = $this->redis()->get($this->getKey($type, $mobile));

        return $sms_code == $code;
    }

    /**
     * 发送验证码
     *
     * @param string $usage 验证码用途
     * @param string $mobile 手机号
     * @return array|bool
     */
    public function send(string $usage, string $mobile)
    {
        if (!$this->isUsages($usage)) {
            return [false, [
                'msg' => "[{$usage}]：此类短信验证码不支持发送",
                'data' => []
            ]];
        }

        $key = $this->getKey($usage, $mobile);

        // 为防止刷短信行为，此处可进行过滤处理
        [$isTrue, $data] = $this->filter($usage, $mobile);
        if (!$isTrue) {
            return [false, $data];
        }

        if (!$sms_code = $this->getCode($key)) {
            $sms_code = mt_rand(100000, 999999);
        }

        // 设置短信验证码
        $this->setCode($key, $sms_code);

        // ... 调取短信接口，建议异步任务执行 (暂无短信接口，省略处理)

        return [true, [
            'msg' => 'success',
            'data' => ['type' => $usage, 'code' => $sms_code]
        ]];
    }

    /**
     * 获取缓存的验证码
     *
     * @param string $key
     * @return mixed
     */
    public function getCode(string $key)
    {
        return $this->redis()->get($key);
    }

    /**
     * 设置验证码缓存
     *
     * @param string $key 缓存key
     * @param string $sms_code 验证码
     * @param float|int $exp 过期时间（默认15分钟）
     * @return mixed
     */
    public function setCode(string $key, string $sms_code, $exp = 60 * 15)
    {
        return $this->redis()->setex($key, $exp, $sms_code);
    }

    /**
     * 删除验证码缓存
     *
     * @param string $usage 验证码用途
     * @param string $mobile 手机号
     * @return mixed
     */
    public function delCode(string $usage, string $mobile)
    {
        return $this->redis()->del($this->getKey($usage, $mobile));
    }

    /**
     * 短信发送过滤验证
     *
     * @param string $usage 验证码用途
     * @param string $mobile 手机号
     * @return array
     */
    public function filter(string $usage, string $mobile)
    {
        // ... 省略处理
        if (false) {
            return [false, [
                'msg' => '过滤原因...',
                'data' => []
            ]];
        }

        return [true, [
            'msg' => 'ok',
            'data' => []
        ]];
    }

    /**
     * 判断验证码用途渠道是否注册
     *
     * @param string $usage
     * @return bool
     */
    public function isUsages(string $usage)
    {
        return in_array($usage, self::SMS_USAGE);
    }
}
