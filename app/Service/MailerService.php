<?php

namespace App\Service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Class MailerService
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
     * @param string $email 邮箱
     * @param string $subject 标题
     * @param string $template 对应邮件模板
     * @return bool
     */
    public function send($email, $subject, $template)
    {
        if ($this->queueSwitch) {

        }

        return $this->realSend($email, $subject, $template);
    }

    /**
     * 发送邮件
     *
     * @param string $email 邮箱
     * @param string $subject 标题
     * @param string $template 对应邮件模板
     * @return bool
     */
    public function realSend($email, $subject, $template)
    {
        try {
            return $this->mail($email, $subject, $template);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 推送邮件
     *
     * @param string $address 收件人
     * @param string $subject 邮件标题
     * @param string $view 邮件内容
     * @return bool
     * @throws Exception
     */
    private function mail(string $address, string $subject, string $view): bool
    {
        $config        = config('mail');
        $mail          = new PHPMailer();
        $mail->CharSet = 'UTF-8';
        $mail->IsSMTP(); // 设定使用SMTP服务
        $mail->SMTPDebug  = 0; // 关闭SMTP调试功能
        $mail->SMTPAuth   = true; // 启用 SMTP 验证功能
        $mail->SMTPSecure = 'ssl'; // 使用安全协议
        $mail->Host       = $config['host'];
        $mail->Port       = $config['port'];
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SetFrom($config['from'], $config['name']);
        $mail->Subject = $subject;
        $mail->MsgHTML($view);
        $mail->AddAddress($address); // 收件人
        return $mail->Send();
    }
}
