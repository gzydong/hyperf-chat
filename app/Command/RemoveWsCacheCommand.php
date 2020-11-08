<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\SocketFDService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class RemoveWsCacheCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('ws:remove-cache');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('清除 WebSocket 客户端 FD 与用户绑定的缓存信息');
    }

    public function handle()
    {
        $socket = new SocketFDService();

        $arr= $socket->getServerRunIdAll(2);

        foreach ($arr as $run_id=>$value){
            $socket->removeRedisCache($run_id);
        }

        $this->line('缓存已清除!', 'info');
    }
}
