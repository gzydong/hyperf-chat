<?php

declare(strict_types=1);

namespace App\Command;

use App\Support\Mail;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

/**
 * 测试发送验证码
 *
 * @Command
 */
class SendEmailCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('ws:send-email');
    }

    public function configure()
    {
        parent::configure();
    }

    public function handle()
    {
        $mail = new Mail();
        $mail->sendEmailCode('837215079@qq.com', '878123', '邮件验证码标题');
    }
}
