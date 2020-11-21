<?php


namespace App\Controller\Api\V1;

use App\Model\Chat\ChatRecord;
use App\Model\Chat\ChatRecordsFile;
use App\Model\Group\UsersGroup;
use App\Service\UploadService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\ResponseInterface;
use App\Middleware\JWTAuthMiddleware;

/**
 * Class DownloadController
 *
 * @Controller(path="/api/v1/download")
 *
 * @package App\Controller\Api\V1
 */
class DownloadController extends CController
{
    /**
     * 下载用户聊天文件
     *
     * @RequestMapping(path="user-chat-file", methods="get")
     *
     * @return mixed
     */
    public function userChatFile(ResponseInterface $response, UploadService $uploadService)
    {
        $crId = $this->request->input('cr_id', 0);
        $uid = 2054;

        $recordsInfo = ChatRecord::select(['msg_type', 'source', 'user_id', 'receive_id'])->where('id', $crId)->first();
        if (!$recordsInfo) {
            return $this->response->fail('文件不存在...');
        }

        //判断消息是否是当前用户发送(如果是则跳过权限验证)
        if ($recordsInfo->user_id != $uid) {
            if ($recordsInfo->source == 1) {
                if ($recordsInfo->receive_id != $uid) {
                    return $this->response->fail('非法请求...');
                }
            } else {
                if (!UsersGroup::isMember($recordsInfo->receive_id, $uid)) {
                    return $this->response->fail('非法请求...');
                }
            }
        }

        $fileInfo = ChatRecordsFile::select(['save_dir', 'original_name'])->where('record_id', $crId)->first();
        if (!$fileInfo) {
            return $this->response->fail('文件不存在或没有下载权限...');
        }

        return $response->download($uploadService->driver($fileInfo->save_dir), $fileInfo->original_name);
    }
}
