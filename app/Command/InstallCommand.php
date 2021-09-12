<?php
declare(strict_types=1);

/**
 * This is my open source code, please do not use it for commercial applications.
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author Yuandong<837215079@qq.com>
 * @link   https://github.com/gzydong/hyperf-chat
 */

namespace App\Command;

use App\Service\RobotService;
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

        di()->get(RobotService::class)->create([
            'robot_name' => "登录助手",
            'describe'   => "异地登录助手",
            'logo'       => '',
            'is_talk'    => 0,
            'type'       => 1,
        ]);

        if ($this->confirm('是否需要生成测试数据？')) {
            $this->call('db:seed');
        }
    }
}
