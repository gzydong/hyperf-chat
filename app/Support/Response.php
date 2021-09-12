<?php
declare(strict_types=1);

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
    public function fail(string $message = 'fail', array $data = [], $code = ResponseCode::FAIL): PsrResponseInterface
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
    public function error(string $message = '', $code = ResponseCode::SERVER_ERROR): PsrResponseInterface
    {
        return $this->response->withStatus(500)->json([
            'code'    => $code,
            'message' => $message,
        ]);
    }
}
