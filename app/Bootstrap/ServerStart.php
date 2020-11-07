<?php


namespace App\Bootstrap;

use App\Service\SocketFDService;
use Hyperf\Framework\Bootstrap\ServerStartCallback;
use Hashids\Hashids;
use Hyperf\Di\Annotation\Inject;

/**
 * 自定义服务启动前回调事件
 *
 * Class ServerStart
 * @package App\Bootstrap
 */
class ServerStart extends ServerStartCallback
{

    /**
     * @inject
     * @var SocketFDService
     */
    private $socketFDService;


    /**
     * 回调事件
     */
    public function beforeStart()
    {
        $hashids = new Hashids('', 8, 'abcdefghijklmnopqrstuvwxyz');

        // 服务运行ID
        define('SERVER_RUN_ID', $hashids->encode(time() . rand(1000, 9999)));

        $this->socketFDService->removeRedisCache();


        stdout_log()->info(sprintf('服务运行ID : %s', SERVER_RUN_ID));
        stdout_log()->info('服务启动前回调事件 : beforeStart ...');
    }
}