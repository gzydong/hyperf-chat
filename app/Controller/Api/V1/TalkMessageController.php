<?php

namespace App\Controller\Api\V1;

use App\Cache\LastMessage;
use App\Cache\UnreadTalk;
use App\Constants\TalkMessageEvent;
use App\Constants\TalkMode;
use App\Model\EmoticonItem;
use App\Model\FileSplitUpload;
use App\Service\TalkMessageService;
use App\Support\MessageProducer;
use App\Support\UserRelation;
use App\Service\EmoticonService;
use App\Service\TalkService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use League\Flysystem\Filesystem;

/**
 * Class TalkController
 * @Controller(prefix="/api/v1/talk/message")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class TalkMessageController extends CController
{
    /**
     * @Inject
     * @var TalkService
     */
    public $talkService;

    /**
     * @Inject
     * @var TalkMessageService
     */
    public $talkMessageService;

    /**
     * 发送代码块消息
     * @RequestMapping(path="code", methods="post")
     */
    public function code()
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'lang', 'code']);
        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
            'lang'        => 'required',
            'code'        => 'required'
        ]);

        $user_id = $this->uid();
        if (!UserRelation::isFriendOrGroupMember($user_id, $params['receiver_id'], $params['talk_type'])) {
            return $this->response->fail('暂不属于好友关系或群聊成员，无法发送聊天消息！');
        }

        $record_id = $this->talkMessageService->insertCodeMessage([
            'talk_type'   => $params['talk_type'],
            'user_id'     => $user_id,
            'receiver_id' => $params['receiver_id'],
        ], [
            'user_id'   => $user_id,
            'code_lang' => $params['lang'],
            'code'      => $params['code']
        ]);

        if (!$record_id) return $this->response->fail('消息发送失败！');

        MessageProducer::publish(MessageProducer::create(TalkMessageEvent::EVENT_TALK, [
            'sender_id'   => $user_id,
            'receiver_id' => (int)$params['receiver_id'],
            'talk_type'   => (int)$params['talk_type'],
            'record_id'   => $record_id
        ]));

        LastMessage::getInstance()->save((int)$params['talk_type'], $user_id, (int)$params['receiver_id'], [
            'text'       => '[代码消息]',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response->success();
    }

    /**
     * 发送图片消息
     * @RequestMapping(path="image", methods="post")
     */
    public function image(Filesystem $filesystem)
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id']);
        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1'
        ]);

        $user_id = $this->uid();
        if (!UserRelation::isFriendOrGroupMember($user_id, $params['receiver_id'], $params['talk_type'])) {
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
            $path = 'media/images/talks/' . date('Ymd') . '/' . create_image_name($ext, getimagesize($file->getRealPath()));
            $filesystem->write($path, file_get_contents($file->getRealPath()));
        } catch (\Exception $e) {
            return $this->response->fail();
        }

        // 创建图片消息记录
        $record_id = $this->talkMessageService->insertFileMessage([
            'talk_type'   => $params['talk_type'],
            'user_id'     => $user_id,
            'receiver_id' => $params['receiver_id'],
        ], [
            'user_id'       => $user_id,
            'file_suffix'   => $ext,
            'file_size'     => $file->getSize(),
            'save_dir'      => $path,
            'original_name' => $file->getClientFilename(),
        ]);

        if (!$record_id) return $this->response->fail('图片上传失败！');

        MessageProducer::publish(MessageProducer::create(TalkMessageEvent::EVENT_TALK, [
            'sender_id'   => $user_id,
            'receiver_id' => (int)$params['receiver_id'],
            'talk_type'   => (int)$params['talk_type'],
            'record_id'   => $record_id,
        ]));

        LastMessage::getInstance()->save((int)$params['talk_type'], $user_id, (int)$params['receiver_id'], [
            'text'       => '[图片消息]',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response->success();
    }

    /**
     * 发送文件消息
     * @RequestMapping(path="file", methods="post")
     */
    public function file(Filesystem $filesystem)
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'hash_name']);
        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
            'hash_name'   => 'required',
        ]);

        $user_id = $this->uid();
        if (!UserRelation::isFriendOrGroupMember($user_id, $params['receiver_id'], $params['talk_type'])) {
            return $this->response->fail('暂不属于好友关系或群聊成员，无法发送聊天消息！');
        }

        $file = FileSplitUpload::where('user_id', $user_id)->where('hash_name', $params['hash_name'])->where('file_type', 1)->first();
        if (!$file || empty($file->save_dir)) {
            return $this->response->fail('文件不存在...');
        }

        try {
            $save_dir = "files/talks/" . date('Ymd') . '/' . create_random_filename($file->file_ext);
            $filesystem->copy($file->save_dir, $save_dir);
        } catch (\Exception $e) {
            return $this->response->fail('文件不存在...');
        }

        $record_id = $this->talkMessageService->insertFileMessage([
            'talk_type'   => $params['talk_type'],
            'user_id'     => $user_id,
            'receiver_id' => $params['receiver_id']
        ], [
            'user_id'       => $user_id,
            'file_source'   => 1,
            'original_name' => $file->original_name,
            'file_suffix'   => $file->file_ext,
            'file_size'     => $file->file_size,
            'save_dir'      => $save_dir,
        ]);

        if (!$record_id) return $this->response->fail('表情发送失败！');

        MessageProducer::publish(MessageProducer::create(TalkMessageEvent::EVENT_TALK, [
            'sender_id'   => $user_id,
            'receiver_id' => (int)$params['receiver_id'],
            'talk_type'   => (int)$params['talk_type'],
            'record_id'   => $record_id
        ]));

        LastMessage::getInstance()->save((int)$params['talk_type'], $user_id, (int)$params['receiver_id'], [
            'text'       => '[文件消息]',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response->success();
    }

    /**
     * 发送投票消息
     * @RequestMapping(path="vote", methods="post")
     */
    public function vote()
    {
        $params = $this->request->all();
        $this->validate($params, [
            'receiver_id' => 'required|integer|min:1',
            'mode'        => 'required|integer|in:0,1',
            'title'       => 'required',
            'options'     => 'required|array',
        ]);

        $user_id = $this->uid();

        $this->talkMessageService->insertVoteMessage([
            'talk_type'   => TalkMode::GROUP_CHAT,
            'user_id'     => $user_id,
            'receiver_id' => $params['receiver_id'],
        ], [
            'mode'    => $params['mode'],
            'title'   => $params['title'],
            'options' => $params['options'],
        ]);
    }

    /**
     * 发送表情包消息
     * @RequestMapping(path="emoticon", methods="post")
     */
    public function emoticon()
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'emoticon_id']);
        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
            'emoticon_id' => 'required|integer|min:1',
        ]);

        $user_id = $this->uid();
        if (!UserRelation::isFriendOrGroupMember($user_id, $params['receiver_id'], $params['talk_type'])) {
            return $this->response->fail('暂不属于好友关系或群聊成员，无法发送聊天消息！');
        }

        $emoticon = EmoticonItem::where('id', $params['emoticon_id'])->where('user_id', $user_id)->first();

        if (!$emoticon) return $this->response->fail('表情不存在！');

        $record_id = $this->talkMessageService->insertFileMessage([
            'talk_type'   => $params['talk_type'],
            'user_id'     => $user_id,
            'receiver_id' => $params['receiver_id'],
        ], [
            'user_id'       => $user_id,
            'file_suffix'   => $emoticon->file_suffix,
            'file_size'     => $emoticon->file_size,
            'save_dir'      => $emoticon->url,
            'original_name' => '图片表情',
        ]);

        if (!$record_id) return $this->response->fail('表情发送失败！');

        MessageProducer::publish(MessageProducer::create(TalkMessageEvent::EVENT_TALK, [
            'sender_id'   => $user_id,
            'receiver_id' => (int)$params['receiver_id'],
            'talk_type'   => (int)$params['talk_type'],
            'record_id'   => $record_id
        ]));

        LastMessage::getInstance()->save((int)$params['talk_type'], $user_id, (int)$params['receiver_id'], [
            'text'       => '[表情包消息]',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response->success();
    }

    /**
     * 转发消息记录
     * @RequestMapping(path="forward", methods="post")
     */
    public function forward()
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'records_ids', 'forward_mode', 'receive_user_ids', 'receive_group_ids']);
        $this->validate($params, [
            'talk_type'    => 'required|in:1,2',
            'receiver_id'  => 'required|integer|min:1',
            'records_ids'  => 'required',       // 聊天记录ID，多个逗号拼接
            'forward_mode' => 'required|in:1,2',// 转发方方式[1:逐条转发;2:合并转发;]
        ]);

        $receive_user_ids = $receive_group_ids = [];
        if (isset($params['receive_user_ids']) && !empty($params['receive_user_ids'])) {
            $receive_user_ids = array_map(function ($friend_id) {
                return ['talk_type' => TalkMode::PRIVATE_CHAT, 'id' => (int)$friend_id];
            }, $params['receive_user_ids']);
        }

        if (isset($params['receive_group_ids']) && !empty($params['receive_group_ids'])) {
            $receive_group_ids = array_map(function ($group_id) {
                return ['talk_type' => TalkMode::GROUP_CHAT, 'id' => (int)$group_id];
            }, $params['receive_group_ids']);
        }

        $items = array_merge($receive_user_ids, $receive_group_ids);

        $user_id = $this->uid();
        if ($params['forward_mode'] == 1) {// 单条转发
            $ids = $this->talkService->forwardRecords($user_id, $params['receiver_id'], $params['records_ids']);
        } else {// 合并转发
            $ids = $this->talkService->mergeForwardRecords($user_id, $params['receiver_id'], $params['talk_type'], $params['records_ids'], $items);
        }

        if (!$ids) return $this->response->fail('转发失败！');

        if ($receive_user_ids) {
            foreach ($receive_user_ids as $v) {
                UnreadTalk::getInstance()->increment($user_id, $v['id']);
            }
        }

        // 消息推送队列
        foreach ($ids as $value) {
            MessageProducer::publish(MessageProducer::create(TalkMessageEvent::EVENT_TALK, [
                'sender_id'   => $user_id,
                'receiver_id' => $value['receiver_id'],
                'talk_type'   => $value['talk_type'],
                'record_id'   => $value['record_id'],
            ]));
        }

        return $this->response->success([], '转发成功...');
    }

    /**
     * 收藏聊天图片
     * @RequestMapping(path="collect", methods="post")
     */
    public function collect(EmoticonService $service)
    {
        $params = $this->request->inputs(['record_id']);
        $this->validate($params, [
            'record_id' => 'required|integer'
        ]);

        [$isTrue, $data] = $service->collect($this->uid(), $params['record_id']);

        if (!$isTrue) return $this->response->fail('添加表情失败！');

        return $this->response->success([
            'emoticon' => $data
        ]);
    }

    /**
     * 撤销聊天记录
     * @RequestMapping(path="revoke", methods="post")
     */
    public function revoke()
    {
        $params = $this->request->inputs(['record_id']);
        $this->validate($params, [
            'record_id' => 'required|integer|min:1'
        ]);

        [$isTrue, $message,] = $this->talkService->revokeRecord($this->uid(), $params['record_id']);
        if (!$isTrue) return $this->response->fail($message);

        MessageProducer::publish(MessageProducer::create(TalkMessageEvent::EVENT_REVOKE_TALK, [
            'record_id' => $params['record_id']
        ]));

        return $this->response->success([], $message);
    }

    /**
     * 删除聊天记录
     * @RequestMapping(path="delete", methods="post")
     */
    public function delete()
    {
        $params = $this->request->inputs(['talk_type', 'receiver_id', 'record_id']);
        $this->validate($params, [
            'talk_type'   => 'required|in:1,2',
            'receiver_id' => 'required|integer|min:1',
            'record_id'   => 'required|ids',
        ]);

        $isTrue = $this->talkService->removeRecords(
            $this->uid(),
            $params['talk_type'],
            $params['receiver_id'],
            parse_ids($params['record_id'])
        );

        return $isTrue
            ? $this->response->success([], '删除成功...')
            : $this->response->fail('删除失败！');
    }
}
