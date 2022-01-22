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

namespace App\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use App\Exception\ValidateException;
use App\Constant\ResponseCode;

abstract class AbstractController
{
    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Inject
     * @var ResponseInterface
     */
    protected $response;

    /**
     * 自定义控制器验证器
     *
     * @param mixed ...$arg
     */
    protected function validate(...$arg)
    {
        $validator = di()->get(ValidatorFactoryInterface::class)->make(...$arg);
        if ($validator->fails()) {
            throw new ValidateException($validator->errors()->first(), ResponseCode::VALIDATION_ERROR);
        }
    }
}
