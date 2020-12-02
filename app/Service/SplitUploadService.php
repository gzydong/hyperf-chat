<?php

namespace App\Service;

use App\Model\FileSplitUpload;
use Hyperf\Utils\Str;
use Hyperf\HttpMessage\Upload\UploadedFile;

/**
 * 文件拆分上传服务
 *
 * Class SplitUploadService
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
     * @param int $user_id 用户ID
     * @param string $fileName 上传的文件名
     * @param string $fileSize 上传文件大小
     *
     * @return array|bool
     */
    public function create(int $user_id, string $fileName, string $fileSize)
    {
        $hash_name = implode('-', [uniqid(), rand(10000000, 99999999), Str::random(6)]);
        $split_num = intval(ceil($fileSize / self::SPLIT_SIZE));

        $data = [];
        $data['file_type'] = 1;
        $data['user_id'] = $user_id;
        $data['original_name'] = $fileName;
        $data['hash_name'] = $hash_name;
        $data['file_ext'] = pathinfo($fileName, PATHINFO_EXTENSION);
        $data['file_size'] = $fileSize;
        $data['upload_at'] = time();

        //文件拆分数量
        $data['split_num'] = $split_num;
        $data['split_index'] = $split_num;

        return FileSplitUpload::create($data) ? array_merge($data, ['split_size' => self::SPLIT_SIZE]) : false;
    }

    /**
     * 保存拆分上传的文件
     *
     * @param int $user_id 用户ID
     * @param UploadedFile $file 文件信息
     * @param string $hashName 上传临时问价hash名
     * @param int $split_index 当前拆分文件索引
     * @param int $fileSize 文件大小
     *
     * @return bool
     */
    public function upload(int $user_id, UploadedFile $file, string $hashName, int $split_index, int $fileSize)
    {
        $fileInfo = FileSplitUpload::select(['id', 'original_name', 'split_num', 'file_ext'])
            ->where([['user_id', '=', $user_id], ['hash_name', '=', $hashName], ['file_type', '=', 1]])
            ->first();

        if (!$fileInfo) {
            return false;
        }

        // 保存文件名及保存文件相对目录
        $fileName = "{$hashName}_{$split_index}_{$fileInfo->file_ext}.tmp";
        $uploadService = make(UploadService::class);

        $uploadService->makeDirectory($uploadService->driver("tmp/{$hashName}"));
        $file->moveTo(sprintf('%s/%s', $uploadService->driver("tmp/{$hashName}"), $fileName));

        if (!$file->isMoved()) {
            return false;
        }

        $info = FileSplitUpload::where('user_id', $user_id)->where('hash_name', $hashName)->where('split_index', $split_index)->first();
        if (!$info) {
            return FileSplitUpload::create([
                'user_id' => $user_id,
                'file_type' => 2,
                'hash_name' => $hashName,
                'original_name' => $fileInfo->original_name,
                'split_index' => $split_index,
                'split_num' => $fileInfo->split_num,
                'save_dir' => sprintf('%s/%s', "tmp/{$hashName}", $fileName),
                'file_ext' => $fileInfo->file_ext,
                'file_size' => $fileSize,
                'upload_at' => time(),
            ]) ? true : false;
        }

        return true;
    }

    /**
     * 文件合并
     *
     * @param int $user_id 用户ID
     * @param string $hash_name 上传临时问价hash名
     *
     * @return array|bool
     */
    public function merge(int $user_id, string $hash_name)
    {
        $fileInfo = FileSplitUpload::select(['id', 'original_name', 'split_num', 'file_ext', 'file_size'])
            ->where('user_id', $user_id)
            ->where('hash_name', $hash_name)
            ->where('file_type', 1)
            ->first();

        if (!$fileInfo) {
            return false;
        }

        $files = FileSplitUpload::where('user_id', $user_id)
            ->where('hash_name', $hash_name)
            ->where('file_type', 2)
            ->orderBy('split_index', 'asc')
            ->get(['split_index', 'save_dir'])->toArray();

        if (!$files) {
            return false;
        }

        if (count($files) != $fileInfo->split_num) {
            return false;
        }

        $fileMerge = "tmp/{$hash_name}/{$fileInfo->original_name}.tmp";
        $uploadService = make(UploadService::class);

        // 文件合并
        $merge_save_path = $uploadService->driver($fileMerge);
        foreach ($files as $file) {
            file_put_contents($merge_save_path, file_get_contents($uploadService->driver($file['save_dir'])), FILE_APPEND);
        }

        FileSplitUpload::select(['id', 'original_name', 'split_num', 'file_ext', 'file_size'])
            ->where('user_id', $user_id)->where('hash_name', $hash_name)
            ->where('file_type', 1)
            ->update(['save_dir' => $fileMerge]);

        return [
            'path' => $fileMerge,
            'tmp_file_name' => "{$fileInfo->original_name}.tmp",
            'original_name' => $fileInfo->original_name,
            'file_size' => $fileInfo->file_size
        ];
    }
}
