<?php

namespace App\Controller\Api\V1;

use App\Service\Group\GroupMemberService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use App\Model\Article\ArticleAnnex;
use App\Model\Talk\TalkRecords;
use App\Model\Talk\TalkRecordsFile;
use Hyperf\HttpServer\Contract\ResponseInterface;
use League\Flysystem\Filesystem;

/**
 * Class DownloadController
 * @Controller(prefix="/api/v1/download")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class DownloadController extends CController
{
    private function getFilePath(string $path)
    {
        return di()->get(Filesystem::class)->getConfig()->get('root') . '/' . $path;
    }

    /**
     * 下载用户聊天文件
     * @RequestMapping(path="user-chat-file", methods="get")
     *
     * @param ResponseInterface $response
     * @param Filesystem        $filesystem
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function userChatFile(ResponseInterface $response, Filesystem $filesystem)
    {
        $params = $this->request->inputs(['cr_id']);
        $this->validate($params, [
            'cr_id' => 'required|integer'
        ]);

        $recordsInfo = TalkRecords::select(['msg_type', 'talk_type', 'user_id', 'receiver_id'])->where('id', $params['cr_id'])->first();
        if (!$recordsInfo) {
            return $this->response->fail('文件不存在！');
        }

        $user_id = $this->uid();

        // 判断消息是否是当前用户发送(如果是则跳过权限验证)
        if ($recordsInfo->user_id != $user_id) {
            if ($recordsInfo->talk_type == 1) {
                if ($recordsInfo->receiver_id != $user_id) {
                    return $this->response->fail('非法请求！');
                }
            } else {
                if (!di()->get(GroupMemberService::class)->isMember($recordsInfo->receiver_id, $user_id)) {
                    return $this->response->fail('非法请求！');
                }
            }
        }

        $info = TalkRecordsFile::select(['save_dir', 'original_name'])->where('record_id', $params['cr_id'])->first();
        if (!$info || !$filesystem->has($info->save_dir)) {
            return $this->response->fail('文件不存在或没有下载权限！');
        }

        return $response->download($this->getFilePath($info->save_dir), $info->original_name);
    }
}
