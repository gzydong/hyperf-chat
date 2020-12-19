<?php

namespace App\Exception\Handler;

use Throwable;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Phper666\JWTAuth\Exception\TokenValidException;
use Hyperf\WebSocketServer\Exception\WebSocketHandeShakeException;

/**
 * Class JwtAuthExceptionHandler
 * @package App\Exception\Handler
 */
class JwtAuthExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        // 判断被捕获到的异常是希望被捕获的异常
        if ($throwable instanceof TokenValidException) {
            // 格式化输出
            $data = json_encode([
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
                'data' => []
            ], JSON_UNESCAPED_UNICODE);

            // 阻止异常冒泡
            $this->stopPropagation();
            return $response->withAddedHeader('content-type', 'application/json; charset=utf-8')->withStatus(401)->withBody(new SwooleStream($data));
        }

        return $response;
    }

    /**
     * 判断该异常处理器是否要对该异常进行处理
     *
     * @param Throwable $throwable
     * @return bool
     */
    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
