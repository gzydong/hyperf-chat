<?php
declare(strict_types=1);

namespace App\Support;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use App\Constant\ResponseCode;

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
    public function json($data): PsrResponseInterface
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
    public function success(array $data = [], string $message = 'success'): PsrResponseInterface
    {
        $resp = [
            "code"    => ResponseCode::SUCCESS,
            "message" => $message,
            "data"    => $data,
        ];

        return $this->response->json($resp);
    }

    /**
     * 处理失败信息返回
     *
     * @param array  $data    响应数据
     * @param string $message 响应提示
     * @param int    $code    错误码
     * @return PsrResponseInterface
     */
    public function fail(string $message = 'fail', array $data = [], int $code = ResponseCode::FAIL): PsrResponseInterface
    {
        $resp = [
            "code"    => $code,
            "message" => $message,
        ];

        if ($data) $resp["data"] = $data;

        return $this->response->json($resp);
    }

    /**
     * 参数验证错误
     * @param string $message 错误信息
     * @return PsrResponseInterface
     */
    public function invalidParams(string $message = 'fail'): PsrResponseInterface
    {
        return $this->response->json([
            "code"    => ResponseCode::VALIDATION_ERROR,
            "message" => $message,
        ]);
    }

    /**
     * 处理错误信息返回
     *
     * @param string $message 响应提示
     * @param int    $code    错误码
     * @return PsrResponseInterface
     */
    public function error(string $message = '', $code = ResponseCode::SERVER_ERROR): PsrResponseInterface
    {
        return $this->response->withStatus(500)->json([
            'code'    => $code,
            'message' => $message,
        ]);
    }
}
