<?php

namespace App\Support;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use App\Constants\ResponseCode;

class Response
{
    /**
     * @Inject
     * @var ResponseInterface|mixed
     */
    private $response;

    /**
     * 处理json数据
     *
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
     * @param array  $data    响应数据
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
     * @param array  $data    响应数据
     * @param string $message 响应提示
     * @param int    $code    错误码
     * @return PsrResponseInterface
     */
    public function fail($message = 'fail', $data = [], $code = ResponseCode::FAIL)
    {
        return $this->response->json(compact('code', 'message', 'data'));
    }

    /**
     * 处理错误信息返回
     *
     * @param string $message 响应提示
     * @param int    $code    错误码
     * @return PsrResponseInterface
     */
    public function error($message = '', $code = ResponseCode::SERVER_ERROR)
    {
        return $this->response->withStatus(500)->json([
            'code'    => $code,
            'message' => $message,
        ]);
    }
}
