<?php


namespace App\Service;

use Hyperf\HttpMessage\Upload\UploadedFile;
use League\Flysystem\Filesystem;

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
     * @param $dir
     */
    public function makeDirectory($dir)
    {
        if (!file_exists($dir)) @mkdir($dir, 0777, true);
    }

    /**
     * 上传媒体图片
     *
     * @param UploadedFile $file
     * @param string $dir
     * @param string $filename 文件夹名称
     */
    public function media(UploadedFile $file, string $dir, string $filename)
    {
        $save_dir = $this->driver($dir);
        $this->makeDirectory($dir);

        $file->moveTo(sprintf('%s/%s', $save_dir, $filename));
        return $file->isMoved() ? sprintf('/%s/%s', trim($dir, '/'), $filename) : false;
    }
}
