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

namespace App\Middleware;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Http Token 授权验证中间件
 *
 * @package App\Middleware
 */
class JWTAuthMiddleware implements MiddlewareInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    /**
     * 授权验证守卫
     *
     * @var string
     */
    private $guard = 'jwt';

    public function __construct(HttpResponse $response, RequestInterface $request)
    {
        $this->response = $response;
        $this->request  = $request;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (auth($this->guard)->guest()) {
            return $this->response->withStatus(401)->json([
                'code'    => 401,
                'message' => 'Token authentication does not pass !',
            ]);
        }

        return $handler->handle($request);
    }
}
