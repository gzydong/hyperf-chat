<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\SplitUploadService;
use App\Service\UploadService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Phper666\JWTAuth\Middleware\JWTAuthMiddleware;

/**
 * 上传控制器
 *
 * Class UploadController
 *
 * @Controller(path="/api/v1/upload")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class UploadController extends CController
{
    /**
     * @inject
     * @var UploadService
     */
    private $uploadService;

    /**
     * @inject
     * @var SplitUploadService
     */
    private $splitUploadService;

    /**
     * 获取拆分文件信息
     *
     * @RequestMapping(path="get-file-split-info", methods="get")
     *
     */
    public function getFileSplitInfo()
    {
        $params = $this->request->inputs(['file_name', 'file_size']);
        $this->validate($params, [
            'file_name' => "required",
            'file_size' => 'required|integer'
        ]);

        $data = $this->splitUploadService->create($this->uid(), $params['file_name'], $params['file_size']);

        return $data ? $this->response->success($data) : $this->response->fail('获取文件拆分信息失败...');
    }

    /**
     *
     * @RequestMapping(path="file-subarea-upload", methods="post")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function fileSubareaUpload()
    {
        $file = $this->request->file('file');
        $params = $this->request->inputs(['name', 'hash', 'ext', 'size', 'split_index', 'split_num']);
        $this->validate($params, [
            'name' => "required",
            'hash' => 'required',
            'ext' => 'required',
            'size' => 'required',
            'split_index' => 'required',
            'split_num' => 'required'
        ]);

        if (!$file->isValid()) {
            return $this->response->fail();
        }

        $user_id = $this->uid();
        $uploadRes = $this->splitUploadService->upload($user_id, $file, $params['hash'], intval($params['split_index']), intval($params['size']));
        if (!$uploadRes) {
            return $this->response->fail('上传文件失败...');
        }

        if (($params['split_index'] + 1) == $params['split_num']) {
            $fileInfo = $this->splitUploadService->merge($user_id, $params['hash']);
            if (!$fileInfo) {
                return $this->response->fail('上传文件失败...');
            }

            return $this->response->success([
                'is_file_merge' => true,
                'hash' => $params['hash']
            ]);
        }

        return $this->response->success(['is_file_merge' => false]);
    }
}
