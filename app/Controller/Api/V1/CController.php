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

namespace App\Controller\Api\V1;

use Hyperf\Di\Annotation\Inject;
use App\Controller\AbstractController;
use App\Support\Response;
use App\Model\User;

/**
 * 基类控制器
 *
 * @package App\Controller\Api\V1
 */
class CController extends AbstractController
{
    /**
     * @Inject
     * @var Response
     */
    protected $response;

    /**
     * 获取当前登录用户ID
     *
     * @return int
     */
    public function uid(): int
    {
        $guard = $this->guard();

        return $guard->check() ? $guard->user()->getId() : 0;
    }

    /**
     * 获取登录用户信息
     *
     * @return User|null
     */
    public function user(): ?User
    {
        $guard = $this->guard();

        return $guard->check() ? $guard->user() : null;
    }

    /**
     * 获取授权守卫
     *
     * @return mixed|\Qbhy\HyperfAuth\AuthGuard|\Qbhy\HyperfAuth\AuthManager
     */
    public function guard()
    {
        return auth('jwt');
    }
}
