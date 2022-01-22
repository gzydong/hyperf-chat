<?php
declare(strict_types=1);

namespace App\Service;

use App\Constant\FileDriveConstant;
use App\Model\SplitUpload;
use Hyperf\HttpMessage\Upload\UploadedFile;
use League\Flysystem\Filesystem;

/**
 * 文件拆分上传服务
 *
 * @package App\Service
 */
class SplitUploadService
{
    /**
     * 文件拆分的大小
     */
    const SPLIT_SIZE = 2 * 1024 * 1024;

    /**
     * 创建文件拆分相关信息
     *
     * @param int    $user_id  用户ID
     * @param string $fileName 文件名
     * @param int    $fileSize 文件大小
     * @return array|bool
     */
    public function create(int $user_id, string $fileName, int $fileSize)
    {
        $split_num = intval(ceil($fileSize / self::SPLIT_SIZE));

        $data                  = [];
        $data['type']          = 1;
        $data['drive']         = FileDriveConstant::Local;
        $data['upload_id']     = uniqid(strval(time()));
        $data['user_id']       = $user_id;
        $data['original_name'] = $fileName;
        $data['file_ext']      = pathinfo($fileName, PATHINFO_EXTENSION);
        $data['file_size']     = $fileSize;
        $data['attr']          = new \stdClass();
        $data['created_at']    = date("Y-m-d H:i:s");
        $data['updated_at']    = date("Y-m-d H:i:s");

        $data['path'] = sprintf("private/tmp/multipart/%s/%s.tmp", date("Ymd"), md5($data['upload_id']));

        // 文件拆分数量
        $data['split_num']   = $split_num;
        $data['split_index'] = $split_num;

        return SplitUpload::create($data) ? array_merge($data, ['split_size' => self::SPLIT_SIZE]) : false;
    }

    /**
     * 保存拆分上传的文件
     *
     * @param int          $user_id     用户ID
     * @param UploadedFile $file        文件信息
     * @param string       $upload_id   上传临时问价hash名
     * @param int          $split_index 当前拆分文件索引
     * @return bool
     */
    public function upload(int $user_id, UploadedFile $file, string $upload_id, int $split_index)
    {
        $fileInfo = SplitUpload::select(['id', 'original_name', 'split_num', 'file_ext'])
            ->where([['user_id', '=', $user_id], ['upload_id', '=', $upload_id], ['type', '=', 1]])
            ->first();

        if (!$fileInfo) return false;

        $path = sprintf("private/tmp/%s/%s/%d-%s.tmp", date("Ymd"), md5($upload_id), $split_index, $upload_id);

        try {
            di()->get(Filesystem::class)->write($path, file_get_contents($file->getRealPath()));
        } catch (\Exception $e) {
            return false;
        }

        $info = SplitUpload::where('user_id', $user_id)->where('upload_id', $upload_id)->where('split_index', $split_index)->first();

        if (!$info) {
            return (bool)SplitUpload::create([
                'user_id'       => $user_id,
                'type'          => 2,
                'drive'         => FileDriveConstant::Local,
                'upload_id'     => $upload_id,
                'original_name' => $fileInfo->original_name,
                'split_index'   => $split_index,
                'split_num'     => $fileInfo->split_num,
                'path'          => $path,
                'attr'          => new \stdClass(),
                'file_ext'      => $fileInfo->file_ext,
                'file_size'     => $file->getSize(),
                'created_at'    => date("Y-m-d H:i:s"),
                'updated_at'    => date("Y-m-d H:i:s"),
            ]);
        }

        return true;
    }

    /**
     * 文件合并
     *
     * @param int    $user_id   用户ID
     * @param string $upload_id 上传临时问价hash名
     * @return array|bool
     */
    public function merge(int $user_id, string $upload_id)
    {
        $fileInfo = SplitUpload::select(['id', 'original_name', 'split_num', 'file_ext', 'file_size', 'path'])
            ->where('user_id', $user_id)
            ->where('upload_id', $upload_id)
            ->where('type', 1)
            ->first();

        if (!$fileInfo) return false;

        $files = SplitUpload::where('user_id', $user_id)
            ->where('upload_id', $upload_id)
            ->where('type', 2)
            ->orderBy('split_index')
            ->get(['split_index', 'path'])->toArray();

        if (!$files || count($files) != $fileInfo->split_num) return false;

        $filesystem = di()->get(Filesystem::class);
        $root_path  = $filesystem->getConfig()->get('root');

        @mkdir(pathinfo("{$root_path}/{$fileInfo->path}", PATHINFO_DIRNAME));

        foreach ($files as $file) {
            file_put_contents("{$root_path}/{$fileInfo->path}", $filesystem->read($file['path']), FILE_APPEND);
        }

        return [
            'path'          => $fileInfo->path,
            'tmp_file_name' => "{$fileInfo->original_name}.tmp",
            'original_name' => $fileInfo->original_name,
            'file_size'     => $fileInfo->file_size
        ];
    }


    /**
     * 清理超过24小时的临时文件
     */
    public function clear()
    {
        // 24小时前
        $time = time() - 60 * 60 * 24 * 1;

        SplitUpload::where('file_type', 1)->where('upload_at', '<', $time)->select('upload_id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                @di()->get(Filesystem::class)->deleteDir(pathinfo($row->path, PATHINFO_DIRNAME));

                @SplitUpload::where('upload_id', $row->upload_id)->delete();
            }
        });
    }
}
