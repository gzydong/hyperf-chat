<?php
/**
 *
 * This is my open source code, please do not use it for commercial applications.
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author Yuandong<837215079@qq.com>
 * @link   https://github.com/gzydong/hyperf-chat
 */

namespace App\Controller\Api\V1;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use App\Model\Article\ArticleAnnex;
use App\Model\Chat\ChatRecord;
use App\Model\Chat\ChatRecordsFile;
use App\Model\Group\UsersGroup;
use App\Service\UploadService;
use Hyperf\HttpServer\Contract\ResponseInterface;

/**
 * Class DownloadController
 *
 * @Controller(path="/api/v1/download")
 * @Middleware(JWTAuthMiddleware::class)
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
     * @param ResponseInterface $response
     * @param UploadService $uploadService
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function userChatFile(ResponseInterface $response, UploadService $uploadService)
    {
        $params = $this->request->inputs(['cr_id']);
        $this->validate($params, [
            'cr_id' => 'required|integer'
        ]);

        $recordsInfo = ChatRecord::select(['msg_type', 'source', 'user_id', 'receive_id'])->where('id', $params['cr_id'])->first();
        if (!$recordsInfo) {
            return $this->response->fail('文件不存在...');
        }

        $user_id = $this->uid();

        //判断消息是否是当前用户发送(如果是则跳过权限验证)
        if ($recordsInfo->user_id != $user_id) {
            if ($recordsInfo->source == 1) {
                if ($recordsInfo->receive_id != $user_id) {
                    return $this->response->fail('非法请求...');
                }
            } else {
                if (!UsersGroup::isMember($recordsInfo->receive_id, $user_id)) {
                    return $this->response->fail('非法请求...');
                }
            }
        }

        $fileInfo = ChatRecordsFile::select(['save_dir', 'original_name'])->where('record_id', $params['cr_id'])->first();
        if (!$fileInfo) {
            return $this->response->fail('文件不存在或没有下载权限...');
        }

        return $response->download($uploadService->driver($fileInfo->save_dir), $fileInfo->original_name);
    }

    /**
     * 下载笔记附件
     *
     * @RequestMapping(path="article-annex", methods="get")
     *
     * @param ResponseInterface $response
     * @param UploadService $uploadService
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function articleAnnex(ResponseInterface $response, UploadService $uploadService)
    {
        $params = $this->request->inputs(['annex_id']);
        $this->validate($params, [
            'annex_id' => 'required|integer'
        ]);

        $info = ArticleAnnex::select(['save_dir', 'original_name'])
            ->where('id', $params['annex_id'])
            ->where('user_id', $this->uid())
            ->first();

        if (!$info) {
            return $this->response->fail('文件不存在或没有下载权限...');
        }

        return $response->download($uploadService->driver($info->save_dir), $info->original_name);
    }
}
