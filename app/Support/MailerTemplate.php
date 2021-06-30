<?php

namespace App\Support;

use Throwable;

/**
 * 邮件视图模板
 *
 * @package App\Support
 */
class MailerTemplate
{
    /**
     * 获取模板信息
     *
     * @param string $engine   模板引擎
     * @param string $template 模板名称
     * @param array  $params   模板参数
     * @return string
     */
    private function view(string $engine, string $template, $params = []): string
    {
        return container()->get($engine)->render($template, $params, config('view.config', []));
    }

    /**
     * 验证码通知 - 邮件模板
     *
     * @param string $sms_code 验证码
     * @param array  $params   模板参数
     * @return string
     */
    public function emailCode(string $sms_code, array $params = [])
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
    public function errorNotice(Throwable $throwable)
    {
        return $this->view(config('view.engine'), 'emails.error-notice', [
            'throwable' => $throwable->getTraceAsString(),
            'message'   => $throwable->getMessage()
        ]);
    }
}
