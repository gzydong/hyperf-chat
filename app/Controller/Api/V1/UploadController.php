<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use App\Service\SplitUploadService;
use League\Flysystem\Filesystem;
use Psr\Http\Message\ResponseInterface;

/**
 * 上传文件控制器
 * @Controller(prefix="/api/v1/upload")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class UploadController extends CController
{
    /**
     * @inject
     * @var SplitUploadService
     */
    private $splitUploadService;

    /**
     * 图片文件流上传接口
     *
     * @RequestMapping(path="file-stream", methods="post")
     * @param Filesystem $filesystem
     * @return ResponseInterface
     */
    public function fileStream(Filesystem $filesystem): ResponseInterface
    {
        $fileStream = $this->request->post('fileStream', '');
        if (empty($fileStream)) {
            return $this->response->fail();
        }

        $path = 'media/images/avatar/' . date('Ymd') . '/' . create_random_filename('png');
        try {
            $filesystem->write($path, base64_decode(str_replace(['data:image/png;base64,', ' '], ['', '+'], $fileStream)));
        } catch (\Exception $e) {
            return $this->response->fail();
        }

        return $this->response->success(['avatar' => get_media_url($path)]);
    }

    /**
     * 获取拆分文件信息
     *
     * @RequestMapping(path="get-file-split-info", methods="get")
     */
    public function getFileSplitInfo(): ResponseInterface
    {
        $params = $this->request->inputs(['file_name', 'file_size']);
        $this->validate($params, [
            'file_name' => "required",
            'file_size' => 'required|integer'
        ]);

        $data = $this->splitUploadService->create($this->uid(), $params['file_name'], $params['file_size']);

        return $data ? $this->response->success($data) : $this->response->fail('获取文件拆分信息失败！');
    }

    /**
     * 文件拆分上传接口
     *
     * @RequestMapping(path="file-subarea-upload", methods="post")
     */
    public function fileSubareaUpload(): ResponseInterface
    {
        $file   = $this->request->file('file');
        $params = $this->request->inputs(['name', 'hash', 'ext', 'size', 'split_index', 'split_num']);
        $this->validate($params, [
            'name'        => "required",
            'hash'        => 'required',
            'ext'         => 'required',
            'size'        => 'required',
            'split_index' => 'required',
            'split_num'   => 'required'
        ]);

        if (!$file || !$file->isValid()) {
            return $this->response->fail();
        }

        $user_id   = $this->uid();
        $uploadRes = $this->splitUploadService->upload($user_id, $file, $params['hash'], intval($params['split_index']), intval($params['size']));
        if (!$uploadRes) {
            return $this->response->fail('上传文件失败！');
        }

        if (($params['split_index'] + 1) == $params['split_num']) {
            $fileInfo = $this->splitUploadService->merge($user_id, $params['hash']);
            if (!$fileInfo) {
                return $this->response->fail('上传文件失败！');
            }

            return $this->response->success([
                'is_file_merge' => true,
                'hash'          => $params['hash']
            ]);
        }

        return $this->response->success(['is_file_merge' => false]);
    }
}
