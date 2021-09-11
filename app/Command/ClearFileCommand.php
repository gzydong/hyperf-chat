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

use App\Service\SplitUploadService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class ClearFileCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('system:clear-file');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('清除拆分上传的临时文件！');
    }

    public function handle()
    {
        $this->line('文件清理中...');

        di()->get(SplitUploadService::class)->clear();

        $this->info('文件清理完成...');
    }
}
