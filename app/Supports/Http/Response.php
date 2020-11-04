<?php

namespace App\Supports\Http;


use Hyperf\HttpMessage\Cookie\Cookie;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Utils\Context;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use App\Constants\ResponseCode;

class Response
{
    /**
     * @var ResponseInterface
     */
    protected $response;

    public function __construct()
    {
        $this->response = container()->get(ResponseInterface::class);
    }

    /**
     * @param $data
     * @return PsrResponseInterface
     */
    public function json($data)
    {
        return $this->response->json($data);
    }

    /**
     * 处理成功信息返回
     *
     * @param array $data 响应数据
     * @param string $message 响应提示
     * @return PsrResponseInterface
     */
    public function success(array $data = [], $message = 'success')
    {
        $code = ResponseCode::SUCCESS;
        return $this->response->json(compact('code', 'message', 'data'));
    }

    /**
     * 处理失败信息返回
     *
     * @param array $data 响应数据
     * @param string $message 响应提示
     * @param int $code 错误码
     *
     * @return PsrResponseInterface
     */
    public function fail($message = 'FAIL', $data = [], $code = ResponseCode::FAIL)
    {
        return $this->response->json(compact('code', 'message', 'data'));
    }

    /**
     * @param string $message
     * @param int $code
     * @return PsrResponseInterface
     */
    public function error($message = '', $code = ResponseCode::SERVER_ERROR)
    {
        return $this->response->withStatus(500)->json([
            'code' => $code,
            'message' => $message,
        ]);
    }

    public function redirect($url, $status = 302)
    {
        return $this->response()
            ->withAddedHeader('Location', (string)$url)
            ->withStatus($status);
    }

    public function cookie(Cookie $cookie)
    {
        $response = $this->response()->withCookie($cookie);
        Context::set(PsrResponseInterface::class, $response);
        return $this;
    }

    /**
     * @return \Hyperf\HttpMessage\Server\Response
     */
    public function response()
    {
        return Context::get(PsrResponseInterface::class);
    }
}
