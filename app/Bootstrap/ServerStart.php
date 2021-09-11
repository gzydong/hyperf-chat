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

namespace App\Bootstrap;

use App\Cache\ServerRunID;
use Hyperf\Framework\Bootstrap\ServerStartCallback;
use Swoole\Timer;

/**
 * 自定义服务启动前回调事件
 *
 * @package App\Bootstrap
 */
class ServerStart extends ServerStartCallback
{
    public function beforeStart()
    {
        stdout_log()->info(sprintf('服务运行ID : %s', SERVER_RUN_ID));

        // 维护服务运行状态
        $this->setRunIdTime();
        Timer::tick(15000, function () {
            $this->setRunIdTime();
        });
    }

    /**
     * 更新运行ID时间
     */
    public function setRunIdTime()
    {
        ServerRunID::getInstance()->add(SERVER_RUN_ID, time());
    }
}
