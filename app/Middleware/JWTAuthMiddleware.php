<?php

namespace App\Middleware;

use Phper666\JWTAuth\Middleware\JWTAuthMiddleware as BaseJWTAuthMiddleware;
use Phper666\JWTAuth\Exception\TokenValidException;
use Phper666\JWTAuth\Util\JWTUtil;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\InvalidArgumentException;

class JWTAuthMiddleware extends BaseJWTAuthMiddleware
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws InvalidArgumentException
     * @throws \Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isValidToken = false;
        // 根据具体业务判断逻辑走向，这里假设用户携带的token有效
        $token = $request->getHeaderLine('Authorization') ?? '';
        if (strlen($token) > 0) {
            try {
                $token = JWTUtil::handleToken($token);
                if ($token !== false && $this->jwt->checkToken($token)) {
                    $isValidToken = true;
                }
            } catch (InvalidArgumentException $e) {
                throw new TokenValidException('Token authentication does not pass', 401);
            }
        }

        if ($isValidToken) {
            return $handler->handle($request);
        }

        throw new TokenValidException('Token authentication does not pass', 401);
    }
}
