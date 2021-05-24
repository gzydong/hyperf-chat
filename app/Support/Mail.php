<?php

namespace App\Support;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Class Mail
 *
 * @package App\Support
 */
class Mail
{
    /**
     * @param string $address
     * @param string $subject
     * @param string $view
     * @return bool
     */
    public function send(string $address, string $subject, string $view): bool
    {
        try {
            $config        = config('mail');
            $mail          = new PHPMailer();                                                  // PHPMailer对象
            $mail->CharSet = 'UTF-8';                                                          // 设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
            $mail->IsSMTP();                                                                   // 设定使用SMTP服务
            $mail->SMTPDebug  = 0;                                                              // 关闭SMTP调试功能
            $mail->SMTPAuth  = true;                                                           // 启用 SMTP 验证功能
            $mail->SMTPSecure = 'ssl';                                                         // 使用安全协议
            $mail->Host       = $config['host'];                                               // SMTP 服务器
            $mail->Port       = $config['port'];                                               // SMTP服务器的端口号
            $mail->Username   = $config['username'];                                           // SMTP服务器用户名
            $mail->Password   = $config['password'];                                           // SMTP服务器密码
            $mail->SetFrom($config['from'], $config['name']);                                  // 邮箱，昵称
            $mail->Subject = $subject;
            $mail->MsgHTML($view);
            $mail->AddAddress($address); // 收件人
            return $mail->Send();
        } catch (\Exception $e) {
            logger()->error($e->getTraceAsString());
            return false;
        }
    }
}
