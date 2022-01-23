<?php
declare(strict_types=1);

namespace App\Template;

use Throwable;

/**
 * 邮件视图模板库
 *
 * @package App\Templates
 */
class MailerTemplate extends BaseTemplate
{
    /**
     * 验证码通知 - 邮件模板
     *
     * @param string $sms_code 验证码
     * @param array  $params   模板参数
     * @return string
     */
    public function emailCode(string $sms_code, array $params = []): string
    {
        return $this->view(config('view.engine'), 'emails.verify-code', [
            'service_name' => $params['service_name'] ?? "邮箱绑定",
            'sms_code'     => $sms_code,
            'domain'       => $params['web_url'] ?? config('domain.web_url')
        ]);
    }

    /**
     * 系统错误通知 - 邮件模板
     *
     * @param Throwable $throwable
     * @return string
     */
    public function errorNotice(Throwable $throwable): string
    {
        return $this->view(config('view.engine'), 'emails.error-notice', [
            'throwable' => $throwable->getTraceAsString(),
            'message'   => $throwable->getMessage()
        ]);
    }
}
