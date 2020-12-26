<?php
/**
 *
 * This is my open source code, please do not use it for commercial applications.
 *
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
use Phper666\JWTAuth\JWT;
use Phper666\JWTAuth\Util\JWTUtil;
use Hyperf\Utils\Context;

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
     * @var JWT
     */
    protected $jwt;

    public function __construct(HttpResponse $response, RequestInterface $request, JWT $jwt)
    {
        $this->response = $response;
        $this->request = $request;
        $this->jwt = $jwt;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isValidToken = false;

        // 获取请求token
        $token = $request->getHeaderLine('Authorization');
        if (empty($token)) {
            $token = $this->request->input('token', '');
        } else {
            $token = JWTUtil::handleToken($token);
        }

        if (!empty($token)) {
            try {
                if ($token !== false && $this->jwt->checkToken($token)) {
                    $isValidToken = true;
                }
            } catch (\Exception $e) {
            }
        }

        if (!$isValidToken) {
            return $this->response->withStatus(401)->json([
                'code' => 401,
                'message' => 'Token authentication does not pass',
            ]);
        }

        $request = $this->setRequestContext($token);
        return $handler->handle($request);
    }

    private function setRequestContext(string $token): ServerRequestInterface
    {
        $request = Context::get(ServerRequestInterface::class);

        $jwtData = $this->jwt->getParserData($token);

        $request = $request->withAttribute('auth_data', $jwtData);

        Context::set(ServerRequestInterface::class, $request);

        return $request;
    }
}
