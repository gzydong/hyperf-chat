<?php

namespace App\Service;

use Hyperf\HttpMessage\Upload\UploadedFile;

/**
 * 文件上传服务
 *
 * Class UploadService
 * @package App\Service
 */
class UploadService extends BaseService
{
    public function driver($dir)
    {
        return sprintf('%s/%s', rtrim(config('upload_dir'), '/'), trim($dir, '/'));
    }

    /**
     * 创建文件夹
     *
     * @param string $dir 文件夹路径
     */
    public function makeDirectory($dir)
    {
        if (!file_exists($dir)) @mkdir($dir, 0777, true);
    }

    /**
     * 上传媒体图片
     *
     * @param UploadedFile $file
     * @param string $dir 文件夹路径
     * @param string $filename 文件名称
     *
     * @return bool|string
     */
    public function media(UploadedFile $file, string $dir, string $filename)
    {
        $save_dir = $this->driver($dir);

        $this->makeDirectory($save_dir);

        $file->moveTo(sprintf('%s/%s', $save_dir, $filename));

        if ($file->isMoved()) {
            // 修改文件权限
            @chmod(sprintf('%s/%s', $save_dir, $filename), 0644);
        }

        return $file->isMoved() ? sprintf('%s/%s', trim($dir, '/'), $filename) : false;
    }
}
