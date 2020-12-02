<?php

require  './vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


$config = [
    'host' => 'smtp.163.com',
    'port' => 465,
    'username' => '18798276809@163.com',
    'password' => 'RYD18798276809',
    'from' => [
        'address' => '18798276809@163.com',
        'name' => 'Lumen IM 在线聊天',
    ],
    'encryption' => 'ssl',
];


$mail = new PHPMailer(true);
try {
    //Server settings
    $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP(); // 设定使用SMTP服务
    $mail->SMTPDebug  = 0; // 关闭SMTP调试功能
    $mail->SMTPAuth   = true; // 启用 SMTP 验证功能
    $mail->SMTPAutoTLS = false;
    $mail->Host = $config['host'];                    // Set the SMTP server to send through
    $mail->Port = intval($config['port']);                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
    $mail->Username = $config['username'];                     // SMTP username
    $mail->Password = $config['password'];                               // SMTP password
    $mail->SMTPSecure = $config['encryption'];         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged

    //Recipients
    $mail->setFrom($config['from']['address'], $config['from']['name']);

    $mail->addAddress('837215079@qq.com');               // Name is optional

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Here is the subject';
    $mail->Body = 'This is the HTML message body <b>in bold!</b>';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
