<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Talk;

use App\Constant\FileDriveConstant;
use App\Constant\TalkMessageType;
use App\Constant\TalkModeConstant;
use App\Controller\Api\V1\CController;
use App\Model\Talk\TalkRecords;
use App\Model\Talk\TalkRecordsFile;
use App\Service\Group\GroupMemberService;
use App\Service\TalkService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use League\Flysystem\Filesystem;
use Psr\Http\Message\ResponseInterface;

/**
 * Class RecordController
 *
 * @Controller(prefix="/api/v1/talk/records")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1\Talk
 */
class RecordsController extends CController
{
    /**
     * @var TalkService
     */
    private $talkService;

    public function __construct(TalkService $talkService)
    {
        parent::__construct();

        $this->talkService = $talkService;
    }

    /**
     * 获取对话面板中的聊天记录
     *
     * @RequestMapping(path="", methods="get")
     */
    public function records(): ResponseInterface
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'record_id']);

        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
            'record_id'   => 'required|integer|min:0',
        ]);

        $user_id = $this->uid();

        if ($params['talk_type'] == TalkModeConstant::GROUP_CHAT && !di()->get(GroupMemberService::class)->isMember((int)$params['receiver_id'], $user_id)) {
            return $this->response->fail('暂不属于好友关系或群聊成员，无法查看聊天记录！');
        }

        $limit  = 30;
        $result = $this->talkService->getChatRecords(
            $user_id,
            intval($params['receiver_id']),
            intval($params['talk_type']),
            intval($params['record_id']),
            $limit
        );

        return $this->response->success([
            'rows'      => $result,
            'record_id' => $result ? end($result)['id'] : 0,
            'limit'     => $limit
        ]);
    }

    /**
     * 查询聊天历史记录
     *
     * @RequestMapping(path="history", methods="get")
     */
    public function history(): ResponseInterface
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'record_id', 'msg_type']);

        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
            'record_id'   => 'required|integer|min:0',
            'msg_type'    => 'required|integer',
        ]);

        $user_id = $this->uid();

        if ($params['talk_type'] == TalkModeConstant::GROUP_CHAT && !di()->get(GroupMemberService::class)->isMember((int)$params['receiver_id'], $user_id)) {
            return $this->response->fail('暂不属于好友关系或群聊成员，无法查看聊天记录！');
        }

        $types = [
            TalkMessageType::TEXT_MESSAGE,
            TalkMessageType::FILE_MESSAGE,
            TalkMessageType::FORWARD_MESSAGE,
            TalkMessageType::CODE_MESSAGE,
            TalkMessageType::VOTE_MESSAGE
        ];

        if (in_array($params['msg_type'], $types)) {
            $msg_type = [$params['msg_type']];
        } else {
            $msg_type = $types;
        }

        $limit  = 30;
        $result = $this->talkService->getChatRecords(
            $user_id,
            (int)$params['receiver_id'],
            (int)$params['talk_type'],
            (int)$params['record_id'],
            $limit,
            $msg_type
        );

        return $this->response->success([
            'rows'      => $result,
            'record_id' => $result ? end($result)['id'] : 0,
            'limit'     => $limit
        ]);
    }

    /**
     * 获取转发记录详情
     *
     * @RequestMapping(path="forward", methods="get")
     */
    public function forwards(): ResponseInterface
    {
        $params = $this->request->inputs(['record_id']);

        $this->validate($params, [
            'record_id' => 'required|integer|min:1'
        ]);

        $rows = $this->talkService->getForwardRecords($this->uid(), intval($params['record_id']));

        return $this->response->success(['rows' => $rows]);
    }

    /**
     * 获取转发记录详情
     *
     * @RequestMapping(path="file/download", methods="get")
     */
    public function download(\Hyperf\HttpServer\Contract\ResponseInterface $response, Filesystem $filesystem)
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

        $info = TalkRecordsFile::select(['path', 'original_name', "drive"])->where('record_id', $params['cr_id'])->first();

        switch ($info->drive) {
            case FileDriveConstant::Local:
                if (!$info || !$filesystem->has($info->path)) {
                    return $this->response->fail('文件不存在或没有下载权限！');
                }

                return $response->download($this->getFilePath($info->path), $info->original_name);
            case FileDriveConstant::Cos:
                return $this->response->fail('文件驱动不存在！');
            default:
                break;
        }
    }

    private function getFilePath(string $path)
    {
        return di()->get(Filesystem::class)->getConfig()->get('root') . '/' . $path;
    }
}
