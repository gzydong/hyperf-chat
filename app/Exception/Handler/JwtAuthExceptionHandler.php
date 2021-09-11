<?php
declare(strict_types=1);

namespace App\Exception\Handler;

use Qbhy\HyperfAuth\Exception\AuthException;
use Throwable;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;

/**
 * Class JwtAuthExceptionHandler
 *
 * @package App\Exception\Handler
 */
class JwtAuthExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        // 格式化输出
        $data = json_encode([
            'code'    => $throwable->getCode(),
            'message' => $throwable->getMessage(),
        ], JSON_UNESCAPED_UNICODE);

        // 阻止异常冒泡
        $this->stopPropagation();

        return $response->withAddedHeader('content-type', 'application/json; charset=utf-8')->withStatus(401)->withBody(new SwooleStream($data));
    }

    /**
     * 判断该异常处理器是否要对该异常进行处理
     *
     * @param Throwable $throwable
     * @return bool
     */
    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof AuthException;
    }
}
