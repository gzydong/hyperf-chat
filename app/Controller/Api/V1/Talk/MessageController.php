<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Talk;

use App\Cache\UnreadTalkCache;
use App\Constant\MediaTypeConstant;
use App\Constant\TalkEventConstant;
use App\Constant\TalkModeConstant;
use App\Controller\Api\V1\CController;
use App\Event\TalkEvent;
use App\Model\Emoticon\EmoticonItem;
use App\Model\SplitUpload;
use App\Service\TalkForwardService;
use App\Service\TalkMessageService;
use App\Support\UserRelation;
use App\Service\EmoticonService;
use App\Service\TalkService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use League\Flysystem\Filesystem;
use Psr\Http\Message\ResponseInterface;

/**
 * Class TalkController
 *
 * @Controller(prefix="/api/v1/talk/message")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class MessageController extends CController
{
    /**
     * @var TalkService
     */
    public $talkService;

    /**
     * @var TalkMessageService
     */
    public $talkMessageService;

    public function __construct(TalkService $talkService, TalkMessageService $talkMessageService)
    {
        parent::__construct();

        $this->talkService        = $talkService;
        $this->talkMessageService = $talkMessageService;
    }

    /**
     * 发送文本消息
     *
     * @RequestMapping(path="text", methods="post")
     */
    public function text(): ResponseInterface
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'text']);

        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
            'text'        => 'required|max:65535',
        ]);

        $this->talkMessageService->insertText([
            'talk_type'   => $params['talk_type'],
            'user_id'     => $this->uid(),
            'receiver_id' => $params['receiver_id'],
            'content'     => $params['text'],
        ]);

        return $this->response->success();
    }

    /**
     * 发送代码块消息
     *
     * @RequestMapping(path="code", methods="post")
     */
    public function code(): ResponseInterface
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'lang', 'code']);

        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
            'lang'        => 'required',
            'code'        => 'required'
        ]);

        $user_id = $this->uid();

        if (!UserRelation::isFriendOrGroupMember($user_id, (int)$params['receiver_id'], (int)$params['talk_type'])) {
            return $this->response->fail('暂不属于好友关系或群聊成员，无法发送聊天消息！');
        }

        $isTrue = $this->talkMessageService->insertCode([
            'talk_type'   => $params['talk_type'],
            'user_id'     => $user_id,
            'receiver_id' => $params['receiver_id'],
        ], [
            'user_id' => $user_id,
            'lang'    => $params['lang'],
            'code'    => $params['code']
        ]);

        if (!$isTrue) return $this->response->fail('消息发送失败！');

        return $this->response->success();
    }

    /**
     * 发送图片消息
     *
     * @RequestMapping(path="image", methods="post")
     */
    public function image(Filesystem $filesystem): ResponseInterface
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id']);

        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1'
        ]);

        $user_id = $this->uid();

        if (!UserRelation::isFriendOrGroupMember($user_id, (int)$params['receiver_id'], (int)$params['talk_type'])) {
            return $this->response->fail('暂不属于好友关系或群聊成员，无法发送聊天消息！');
        }

        $file = $this->request->file('image');
        if (!$file || !$file->isValid()) {
            return $this->response->fail();
        }

        $ext = $file->getExtension();
        if (!in_array($ext, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
            return $this->response->fail('图片格式错误，目前仅支持jpg、png、jpeg、gif和webp');
        }

        try {
            $path = 'public/media/images/talks/' . date('Ymd') . '/' . create_image_name($ext, getimagesize($file->getRealPath()));
            $filesystem->write($path, file_get_contents($file->getRealPath()));
        } catch (\Exception $e) {
            return $this->response->fail();
        }

        // 创建图片消息记录
        $isTrue = $this->talkMessageService->insertFile([
            'talk_type'   => $params['talk_type'],
            'user_id'     => $user_id,
            'receiver_id' => $params['receiver_id'],
        ], [
            'user_id'       => $user_id,
            'suffix'        => $ext,
            'size'          => $file->getSize(),
            'path'          => $path,
            'url'           => get_media_url($path),
            'original_name' => $file->getClientFilename(),
        ]);

        if (!$isTrue) return $this->response->fail('图片上传失败！');

        return $this->response->success();
    }

    /**
     * 发送文件消息
     *
     * @RequestMapping(path="file", methods="post")
     */
    public function file(Filesystem $filesystem): ResponseInterface
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'upload_id']);

        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
            'upload_id'   => 'required',
        ]);

        $user_id = $this->uid();
        if (!UserRelation::isFriendOrGroupMember($user_id, (int)$params['receiver_id'], (int)$params['talk_type'])) {
            return $this->response->fail('暂不属于好友关系或群聊成员，无法发送聊天消息！');
        }

        $file = SplitUpload::where('user_id', $user_id)->where('upload_id', $params['upload_id'])->where('type', 1)->first();
        if (!$file || empty($file->path)) {
            return $this->response->fail('文件不存在...');
        }

        $save_dir = "private/files/talks/" . date('Ymd') . '/' . create_random_filename($file->file_ext);
        $url      = "";
        if (MediaTypeConstant::getMediaType($file->file_ext) <= 3) {
            $save_dir = "public/media/" . date('Ymd') . '/' . create_random_filename($file->file_ext);
            $url      = get_media_url($save_dir);
        }

        try {
            $filesystem->copy($file->path, $save_dir);
        } catch (\Exception $e) {
            return $this->response->fail('文件不存在...');
        }

        $isTrue = $this->talkMessageService->insertFile([
            'talk_type'   => $params['talk_type'],
            'user_id'     => $user_id,
            'receiver_id' => $params['receiver_id']
        ], [
            'user_id'       => $user_id,
            'source'        => 1,
            'original_name' => $file->original_name,
            'suffix'        => $file->file_ext,
            'size'          => $file->file_size,
            'path'          => $save_dir,
            'url'           => $url,
        ]);

        if (!$isTrue) return $this->response->fail('文件发送失败！');

        return $this->response->success();
    }

    /**
     * 发送投票消息
     *
     * @RequestMapping(path="vote", methods="post")
     */
    public function vote(): ResponseInterface
    {
        $params = $this->request->all();

        $this->validate($params, [
            'receiver_id' => 'required|integer|min:1',
            'mode'        => 'required|integer|in:0,1',
            'title'       => 'required',
            'options'     => 'required|array',
        ]);

        $user_id = $this->uid();
        if (!UserRelation::isFriendOrGroupMember($user_id, (int)$params['receiver_id'], TalkModeConstant::GROUP_CHAT)) {
            return $this->response->fail('暂不属于好友关系或群聊成员，无法发送聊天消息！');
        }

        $isTrue = $this->talkMessageService->insertVote([
            'talk_type'   => TalkModeConstant::GROUP_CHAT,
            'user_id'     => $user_id,
            'receiver_id' => $params['receiver_id'],
        ], [
            'user_id'       => $user_id,
            'title'         => $params['title'],
            'answer_mode'   => $params['mode'],
            'answer_option' => $params['options'],
        ]);

        if (!$isTrue) return $this->response->fail('发起投票失败！');

        return $this->response->success();
    }

    /**
     * 发送表情包消息
     *
     * @RequestMapping(path="emoticon", methods="post")
     */
    public function emoticon(): ResponseInterface
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'emoticon_id']);

        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
            'emoticon_id' => 'required|integer|min:1',
        ]);

        $user_id = $this->uid();
        if (!UserRelation::isFriendOrGroupMember($user_id, (int)$params['receiver_id'], (int)$params['talk_type'])) {
            return $this->response->fail('暂不属于好友关系或群聊成员，无法发送聊天消息！');
        }

        $emoticon = EmoticonItem::where('id', $params['emoticon_id'])->where('user_id', $user_id)->first();

        if (!$emoticon) return $this->response->fail('表情不存在！');

        $isTrue = $this->talkMessageService->insertFile([
            'talk_type'   => $params['talk_type'],
            'user_id'     => $user_id,
            'receiver_id' => $params['receiver_id'],
        ], [
            'user_id'       => $user_id,
            'suffix'        => $emoticon->file_suffix,
            'size'          => $emoticon->file_size,
            'url'           => $emoticon->url,
            'original_name' => '图片表情',
        ]);

        if (!$isTrue) return $this->response->fail('表情发送失败！');

        return $this->response->success();
    }

    /**
     * 发送转发消息
     *
     * @RequestMapping(path="forward", methods="post")
     */
    public function forward(TalkForwardService $forwardService): ResponseInterface
    {
        $params = $this->request->inputs([
            'talk_type', 'receiver_id', 'records_ids', 'forward_mode', 'receive_user_ids', 'receive_group_ids'
        ]);

        $this->validate($params, [
            'talk_type'    => 'required|in:1,2',
            'receiver_id'  => 'required|integer|min:1',
            'records_ids'  => 'required',       // 聊天记录ID，多个逗号拼接
            'forward_mode' => 'required|in:1,2',// 转发方方式[1:逐条转发;2:合并转发;]
        ]);

        $user_id = $this->uid();

        // 判断好友或者群关系
        if (!UserRelation::isFriendOrGroupMember($user_id, (int)$params['receiver_id'], (int)$params['talk_type'])) {
            return $this->response->fail('暂不属于好友关系或群聊成员，无法发送聊天消息！');
        }

        $receive_user_ids = $receive_group_ids = [];

        $func = function (array $ids, int $talk_type) {
            return array_map(function ($id) use ($talk_type) {
                return ['talk_type' => $talk_type, 'id' => (int)$id];
            }, $ids);
        };

        if (isset($params['receive_user_ids']) && !empty($params['receive_user_ids'])) {
            $receive_user_ids = $func(parse_ids($params['receive_user_ids']), TalkModeConstant::PRIVATE_CHAT);
        }

        if (isset($params['receive_group_ids']) && !empty($params['receive_group_ids'])) {
            $receive_group_ids = $func(parse_ids($params['receive_group_ids']), TalkModeConstant::GROUP_CHAT);
        }

        // 需要转发的好友或者群组
        $items = array_merge($receive_user_ids, $receive_group_ids);

        $method = $params['forward_mode'] == 1 ? "multiSplitForward" : "multiMergeForward";

        $ids = $forwardService->{$method}($user_id, (int)$params['receiver_id'], (int)$params['talk_type'], parse_ids($params['records_ids']), $items);

        if (!$ids) return $this->response->fail('转发失败！');

        if ($receive_user_ids) {
            foreach ($receive_user_ids as $v) {
                UnreadTalkCache::getInstance()->increment($user_id, $v['id']);
            }
        }

        // 消息推送队列
        foreach ($ids as $value) {
            event()->dispatch(new TalkEvent(TalkEventConstant::EVENT_TALK, [
                'sender_id'   => $user_id,
                'receiver_id' => $value['receiver_id'],
                'talk_type'   => $value['talk_type'],
                'record_id'   => $value['record_id'],
            ]));
        }

        return $this->response->success([], '转发成功...');
    }

    /**
     * 发送用户名片消息
     *
     * @RequestMapping(path="card", methods="post")
     */
    public function card(): ResponseInterface
    {
        // todo 待开发
    }

    /**
     * 收藏聊天图片
     *
     * @RequestMapping(path="collect", methods="post")
     */
    public function collect(EmoticonService $service): ResponseInterface
    {
        $params = $this->request->inputs(['record_id']);

        $this->validate($params, [
            'record_id' => 'required|integer'
        ]);

        [$isTrue, $data] = $service->collect($this->uid(), (int)$params['record_id']);

        if (!$isTrue) return $this->response->fail('添加表情失败！');

        return $this->response->success([
            'emoticon' => $data
        ]);
    }

    /**
     * 撤销聊天记录
     *
     * @RequestMapping(path="revoke", methods="post")
     */
    public function revoke(): ResponseInterface
    {
        $params = $this->request->inputs(['record_id']);

        $this->validate($params, [
            'record_id' => 'required|integer|min:1'
        ]);

        [$isTrue, $message,] = $this->talkService->revokeRecord($this->uid(), (int)$params['record_id']);
        if (!$isTrue) return $this->response->fail($message);

        return $this->response->success([], $message);
    }

    /**
     * 删除聊天记录
     *
     * @RequestMapping(path="delete", methods="post")
     */
    public function delete(): ResponseInterface
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'record_id']);

        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
            'record_id'   => 'required|ids',
        ]);

        $isTrue = $this->talkService->removeRecords(
            $this->uid(),
            (int)$params['talk_type'],
            (int)$params['receiver_id'],
            parse_ids($params['record_id'])
        );

        if (!$isTrue) {
            return $this->response->fail('删除失败！');
        }

        return $this->response->success([], '删除成功...');
    }

    /**
     * 投票处理
     *
     * @RequestMapping(path="vote/handle", methods="post")
     */
    public function handleVote(): ResponseInterface
    {
        $params = $this->request->inputs(['record_id', 'options']);

        $this->validate($params, [
            'record_id' => 'required|integer|min:1',
            'options'   => 'required',
        ]);

        $params['options'] = array_filter(explode(',', $params['options']));
        if (!$params['options']) {
            return $this->response->fail('投票失败，请稍后再试！');
        }

        [$isTrue, $cache] = $this->talkMessageService->handleVote($this->uid(), $params);

        if (!$isTrue) return $this->response->fail('投票失败，请稍后再试！');

        return $this->response->success($cache);
    }
}
