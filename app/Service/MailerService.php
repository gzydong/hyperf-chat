<?php
declare(strict_types=1);

namespace App\Service;

use App\Support\Mailer;

/**
 * Class MailerService
 *
 * @package App\Service
 */
class MailerService
{
    /**
     * 消息队列
     *
     * @var bool
     */
    public $queueSwitch = false;

    /**
     * 发送邮件
     *
     * @param string $email    邮箱
     * @param string $subject  标题
     * @param string $template 对应邮件模板
     * @return bool
     */
    public function send(string $email, string $subject, string $template): bool
    {
        if ($this->queueSwitch) {

        }

        return $this->realSend($email, $subject, $template);
    }

    /**
     * 发送邮件
     *
     * @param string $email    邮箱
     * @param string $subject  标题
     * @param string $template 对应邮件模板
     * @return bool
     */
    public function realSend(string $email, string $subject, string $template): bool
    {
        return $this->mail($email, $subject, $template);
    }

    /**
     * 推送邮件
     *
     * @param string $address 收件人
     * @param string $subject 邮件标题
     * @param string $view    邮件内容
     * @return bool
     */
    private function mail(string $address, string $subject, string $view): bool
    {
        return di()->get(Mailer::class)->send($address, $subject, $view);
    }
}
