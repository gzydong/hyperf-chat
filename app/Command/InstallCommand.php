<?php
declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class InstallCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('system:install');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('LumenIM 初始化数据表及生成测试数据！');
    }

    public function handle()
    {
        $this->line('LumenIM 正在创建数据库，请耐心等待!', 'info');
        $this->call('migrate');

        if ($this->confirm('是否需要生成测试数据？')) {
            $this->call('db:seed');
        }
    }
}
