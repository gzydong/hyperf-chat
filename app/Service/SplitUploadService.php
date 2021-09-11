<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\FileSplitUpload;
use Hyperf\Utils\Str;
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
     * @param string $fileName 上传的文件名
     * @param string $fileSize 上传文件大小
     * @return array|bool
     */
    public function create(int $user_id, string $fileName, string $fileSize)
    {
        $hash_name = implode('-', [uniqid(), rand(10000000, 99999999), Str::random(6)]);
        $split_num = intval(ceil($fileSize / self::SPLIT_SIZE));

        $data                  = [];
        $data['file_type']     = 1;
        $data['user_id']       = $user_id;
        $data['original_name'] = $fileName;
        $data['hash_name']     = $hash_name;
        $data['file_ext']      = pathinfo($fileName, PATHINFO_EXTENSION);
        $data['file_size']     = $fileSize;
        $data['upload_at']     = time();

        // 文件拆分数量
        $data['split_num']   = $split_num;
        $data['split_index'] = $split_num;

        return FileSplitUpload::create($data) ? array_merge($data, ['split_size' => self::SPLIT_SIZE]) : false;
    }

    /**
     * 保存拆分上传的文件
     *
     * @param int          $user_id     用户ID
     * @param UploadedFile $file        文件信息
     * @param string       $hashName    上传临时问价hash名
     * @param int          $split_index 当前拆分文件索引
     * @param int          $fileSize    文件大小
     * @return bool
     */
    public function upload(int $user_id, UploadedFile $file, string $hashName, int $split_index, int $fileSize)
    {
        $fileInfo = FileSplitUpload::select(['id', 'original_name', 'split_num', 'file_ext'])
            ->where([['user_id', '=', $user_id], ['hash_name', '=', $hashName], ['file_type', '=', 1]])
            ->first();

        if (!$fileInfo) return false;

        $save_path = "tmp/{$hashName}/" . "{$hashName}_{$split_index}_{$fileInfo->file_ext}.tmp";
        try {
            di()->get(Filesystem::class)->write($save_path, file_get_contents($file->getRealPath()));
        } catch (\Exception $e) {
            return false;
        }

        $info = FileSplitUpload::where('user_id', $user_id)->where('hash_name', $hashName)->where('split_index', $split_index)->first();
        if (!$info) {
            return (bool)FileSplitUpload::create([
                'user_id'       => $user_id,
                'file_type'     => 2,
                'hash_name'     => $hashName,
                'original_name' => $fileInfo->original_name,
                'split_index'   => $split_index,
                'split_num'     => $fileInfo->split_num,
                'save_dir'      => $save_path,
                'file_ext'      => $fileInfo->file_ext,
                'file_size'     => $fileSize,
                'upload_at'     => time(),
            ]);
        }

        return true;
    }

    /**
     * 文件合并
     *
     * @param int    $user_id   用户ID
     * @param string $hash_name 上传临时问价hash名
     * @return array|bool
     */
    public function merge(int $user_id, string $hash_name)
    {
        $fileInfo = FileSplitUpload::select(['id', 'original_name', 'split_num', 'file_ext', 'file_size'])
            ->where('user_id', $user_id)
            ->where('hash_name', $hash_name)
            ->where('file_type', 1)
            ->first();

        if (!$fileInfo) return false;

        $files = FileSplitUpload::where('user_id', $user_id)
            ->where('hash_name', $hash_name)
            ->where('file_type', 2)
            ->orderBy('split_index', 'asc')
            ->get(['split_index', 'save_dir'])->toArray();

        if (!$files || count($files) != $fileInfo->split_num) return false;

        $file_merge = "tmp/{$hash_name}/{$fileInfo->original_name}.tmp";

        $filesystem = di()->get(Filesystem::class);
        $root_path  = $filesystem->getConfig()->get('root');
        foreach ($files as $file) {
            file_put_contents(
                "{$root_path}/{$file_merge}",
                $filesystem->read($file['save_dir']),
                FILE_APPEND
            );
        }

        FileSplitUpload::select(['id', 'original_name', 'split_num', 'file_ext', 'file_size'])
            ->where('user_id', $user_id)->where('hash_name', $hash_name)
            ->where('file_type', 1)
            ->update(['save_dir' => $file_merge]);

        return [
            'path'          => $file_merge,
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

        FileSplitUpload::where('file_type', 1)->where('upload_at', '<', $time)->select('hash_name')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                @di()->get(Filesystem::class)->deleteDir("tmp/{$row->hash_name}");
                @FileSplitUpload::where('hash_name', $row->hash_name)->delete();
            }
        });
    }
}
