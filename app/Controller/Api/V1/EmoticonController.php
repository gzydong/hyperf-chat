<?php
/**
 * This is my open source code, please do not use it for commercial applications.
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
use App\Constants\ResponseCode;
use App\Model\Emoticon;
use App\Model\EmoticonItem;
use App\Service\EmoticonService;
use League\Flysystem\Filesystem;
use Psr\Http\Message\ResponseInterface;

/**
 * Class EmoticonController
 * @Controller(prefix="/api/v1/emoticon")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class EmoticonController extends CController
{
    /**
     * @Inject
     * @var EmoticonService
     */
    private $emoticonService;

    /**
     * 获取用户表情包列表
     * @RequestMapping(path="user-emoticon", methods="get")
     *
     * @return ResponseInterface
     */
    public function getUserEmoticon()
    {
        $emoticonList = [];
        $user_id      = $this->uid();

        if ($ids = $this->emoticonService->getInstallIds($user_id)) {
            $items = Emoticon::whereIn('id', $ids)->get(['id', 'name', 'icon']);
            foreach ($items as $item) {
                $emoticonList[] = [
                    'emoticon_id' => $item->id,
                    'url'         => get_media_url($item->icon),
                    'name'        => $item->name,
                    'list'        => $this->emoticonService->getDetailsAll([
                        ['emoticon_id', '=', $item->id],
                        ['user_id', '=', 0]
                    ])
                ];
            }
        }

        return $this->response->success([
            'sys_emoticon'     => $emoticonList,
            'collect_emoticon' => $this->emoticonService->getDetailsAll([
                ['emoticon_id', '=', 0],
                ['user_id', '=', $user_id]
            ])
        ]);
    }

    /**
     * 获取系统表情包
     * @RequestMapping(path="system-emoticon", methods="get")
     *
     * @return ResponseInterface
     */
    public function getSystemEmoticon()
    {
        $items = Emoticon::get(['id', 'name', 'icon'])->toArray();
        if ($items) {
            $ids = $this->emoticonService->getInstallIds($this->uid());
            array_walk($items, function (&$item) use ($ids) {
                $item['status'] = in_array($item['id'], $ids) ? 1 : 0;
                $item['icon']   = get_media_url($item['icon']);
            });
        }

        return $this->response->success($items);
    }

    /**
     * 安装或移除系统表情包
     * @RequestMapping(path="set-user-emoticon", methods="post")
     *
     * @return ResponseInterface
     */
    public function setUserEmoticon()
    {
        $params = $this->request->all();
        $this->validate($params, [
            'emoticon_id' => 'required|integer',
            'type'        => 'required|in:1,2'
        ]);

        $user_id = $this->uid();

        // 移除表情包
        if ($params['type'] == 2) {
            $isTrue = $this->emoticonService->removeSysEmoticon($user_id, $params['emoticon_id']);
            if (!$isTrue) {
                return $this->response->fail('移除表情包失败！');
            }

            return $this->response->success([], '移除表情包成功...');
        }

        // 添加表情包
        $emoticonInfo = Emoticon::where('id', $params['emoticon_id'])->first(['id', 'name', 'icon']);
        if (!$emoticonInfo) {
            return $this->response->fail('添加表情包失败！');
        }

        if (!$this->emoticonService->installSysEmoticon($user_id, $params['emoticon_id'])) {
            return $this->response->fail('添加表情包失败！');
        }

        $data = [
            'emoticon_id' => $emoticonInfo->id,
            'url'         => get_media_url($emoticonInfo->icon),
            'name'        => $emoticonInfo->name,
            'list'        => $this->emoticonService->getDetailsAll([
                ['emoticon_id', '=', $emoticonInfo->id]
            ])
        ];

        return $this->response->success($data, '添加表情包成功...');
    }

    /**
     * 自定义上传表情包
     * @RequestMapping(path="upload-emoticon", methods="post")
     *
     * @param Filesystem $filesystem
     * @return ResponseInterface
     */
    public function uploadEmoticon(Filesystem $filesystem)
    {
        $file = $this->request->file('emoticon');
        if (!$file->isValid()) {
            return $this->response->fail(
                '图片上传失败，请稍后再试！',
                ResponseCode::VALIDATION_ERROR
            );
        }

        $ext = $file->getExtension();
        if (!in_array($ext, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
            return $this->response->fail(
                '图片格式错误，目前仅支持jpg、png、jpeg、gif和webp',
                ResponseCode::VALIDATION_ERROR
            );
        }

        try {
            $path = 'media/images/emoticon/' . date('Ymd') . '/' . create_image_name($ext, getimagesize($file->getRealPath()));
            $filesystem->write($path, file_get_contents($file->getRealPath()));
        } catch (\Exception $e) {
            return $this->response->fail('图片上传失败！');
        }

        $result = EmoticonItem::create([
            'user_id'     => $this->uid(),
            'url'         => $path,
            'file_suffix' => $ext,
            'file_size'   => $file->getSize(),
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        if (!$result) {
            return $this->response->fail('表情包上传失败！');
        }

        return $this->response->success([
            'media_id' => $result->id,
            'src'      => get_media_url($result->url)
        ]);
    }

    /**
     * 移除收藏的表情包
     * @RequestMapping(path="del-collect-emoticon", methods="post")
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function delCollectEmoticon()
    {
        $params = $this->request->inputs(['ids']);
        $this->validate($params, [
            'ids' => 'required|ids'
        ]);

        return $this->emoticonService->deleteCollect($this->uid(), parse_ids($params['ids'])) ?
            $this->response->success([]) :
            $this->response->fail();
    }
}
