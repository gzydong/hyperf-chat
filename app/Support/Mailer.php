<?php
declare(strict_types=1);

namespace App\Support;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Class Mail
 *
 * @package App\Support
 */
class Mailer
{
    /**
     * @param string $address
     * @param string $subject
     * @param string $view
     * @return bool
     */
    public function send(string $address, string $subject, string $view): bool
    {
        $config = config('mail');
        try {
            // PHPMailer对象
            $mail = new PHPMailer();

            // 设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
            $mail->CharSet = 'UTF-8';

            // 设定使用SMTP服务
            $mail->IsSMTP();

            // 关闭SMTP调试功能
            $mail->SMTPDebug = 0;

            // 启用 SMTP 验证功能
            $mail->SMTPAuth = true;

            // 使用安全协议
            $mail->SMTPSecure = 'ssl';

            // SMTP 服务器
            $mail->Host     = $config['host'];
            $mail->Port     = $config['port'];
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];

            // 邮箱，昵称
            $mail->SetFrom($config['from'], $config['name']);
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
