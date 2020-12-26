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
use App\Service\ArticleService;
use App\Service\UploadService;
use App\Support\RedisLock;
use Hyperf\Utils\Str;

/**
 * Class ArticleController
 *
 * @Controller(path="/api/v1/article")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class ArticleController extends CController
{
    /**
     * @Inject
     * @var ArticleService
     */
    private $articleService;

    /**
     * @inject
     * @var UploadService
     */
    private $uploadService;

    /**
     * 获取笔记分类列表
     *
     * @RequestMapping(path="article-class", methods="get")
     */
    public function getArticleClass()
    {
        return $this->response->success([
            'rows' => $this->articleService->getUserClass($this->uid())
        ]);
    }

    /**
     * 获取笔记标签列表
     *
     * @RequestMapping(path="article-tags", methods="get")
     */
    public function getArticleTags()
    {
        return $this->response->success([
            'tags' => $this->articleService->getUserTags($this->uid())
        ]);
    }

    /**
     * 获取笔记列表
     *
     * @RequestMapping(path="article-list", methods="get")
     */
    public function getArticleList()
    {
        $params1 = $this->request->inputs(['keyword', 'find_type', 'cid', 'page']);
        $this->validate($params1, [
            // 搜索关键词
            'keyword' => "present",
            // 查询类型 $findType 1:获取近期日记  2:获取星标日记  3:获取指定分类文章  4:获取指定标签文章 5:获取已删除文章 6:关键词搜索
            'find_type' => 'required|in:0,1,2,3,4,5,6',
            // 分类ID
            'cid' => 'present|integer|min:-1',
            'page' => 'present|integer|min:1'
        ]);

        $params = [];
        $params['find_type'] = $params1['find_type'];
        if (in_array($params1['find_type'], [3, 4])) {
            $params['class_id'] = $params1['cid'];
        }

        if (!empty($params1['keyword'])) {
            $params['keyword'] = $params1['keyword'];
        }

        return $this->response->success(
            $this->articleService->getUserArticleList($this->uid(), 1, 10000, $params)
        );
    }

    /**
     * 获取笔记详情
     *
     * @RequestMapping(path="article-detail", methods="get")
     */
    public function getArticleDetail()
    {
        $params = $this->request->inputs(['article_id']);
        $this->validate($params, [
            'article_id' => 'required|integer'
        ]);

        return $this->response->success(
            $this->articleService->getArticleDetail((int)$params['article_id'], $this->uid())
        );
    }

    /**
     * 添加或编辑笔记分类
     *
     * @RequestMapping(path="edit-article-class", methods="post")
     */
    public function editArticleClass()
    {
        $params = $this->request->inputs(['class_id', 'class_name']);
        $this->validate($params, [
            'class_id' => 'required|integer',
            'class_name' => 'required'
        ]);

        $class_id = $this->articleService->editArticleClass($this->uid(), $params['class_id'], $params['class_name']);
        if (!$class_id) {
            return $this->response->fail('笔记分类编辑失败...');
        }

        return $this->response->success(['id' => $class_id]);
    }

    /**
     * 删除笔记分类
     *
     * @RequestMapping(path="del-article-class", methods="post")
     */
    public function delArticleClass()
    {
        $params = $this->request->inputs(['class_id']);
        $this->validate($params, [
            'class_id' => 'required|integer'
        ]);

        if (!$this->articleService->delArticleClass($this->uid(), (int)$params['class_id'])) {
            return $this->response->fail('笔记分类删除失败...');
        }

        return $this->response->success([], '笔记分类删除成功...');
    }

    /**
     * 笔记分类列表排序接口
     *
     * @RequestMapping(path="article-class-sort", methods="post")
     */
    public function articleClassSort()
    {
        $params = $this->request->inputs(['class_id', 'sort_type']);
        $this->validate($params, [
            'class_id' => 'required|integer',
            'sort_type' => 'required|in:1,2'
        ]);

        $lockKey = "article_class_sort:{$params['class_id']}_{$params['sort_type']}";

        // 获取Redis锁
        if (RedisLock::lock($lockKey, 1, 3)) {
            $isTrue = $this->articleService->articleClassSort($this->uid(), (int)$params['class_id'], (int)$params['sort_type']);

            // 释放Redis锁
            RedisLock::release($lockKey, 1);
        } else {
            $isTrue = false;
        }

        return $isTrue
            ? $this->response->success([], '排序完成...')
            : $this->response->fail('排序失败...');
    }

    /**
     * 笔记分类合并接口
     *
     * @RequestMapping(path="merge-article-class", methods="post")
     */
    public function mergeArticleClass()
    {
        $params = $this->request->inputs(['class_id', 'toid']);
        $this->validate($params, [
            'class_id' => 'required|integer',
            'toid' => 'required|integer'
        ]);

        $isTrue = $this->articleService->mergeArticleClass($this->uid(), (int)$params['class_id'], (int)$params['toid']);

        return $isTrue
            ? $this->response->success([], '合并完成...')
            : $this->response->fail('合并完成...');
    }

    /**
     * 添加或编辑笔记标签
     *
     * @RequestMapping(path="edit-article-tag", methods="post")
     */
    public function editArticleTags()
    {
        $params = $this->request->inputs(['tag_id', 'tag_name']);
        $this->validate($params, [
            'tag_id' => 'required|integer|min:0',
            'tag_name' => 'required'
        ]);

        $id = $this->articleService->editArticleTag($this->uid(), (int)$params['tag_id'], $params['tag_name']);

        return $id
            ? $this->response->success(['id' => $id])
            : $this->response->fail('笔记标签编辑失败...');
    }

    /**
     * 删除笔记标签
     *
     * @RequestMapping(path="del-article-tag", methods="post")
     */
    public function delArticleTags()
    {
        $params = $this->request->inputs(['tag_id']);
        $this->validate($params, [
            'tag_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->delArticleTags($this->uid(), (int)$params['tag_id']);

        return $isTrue
            ? $this->response->success([], '笔记标签删除完成...')
            : $this->response->fail('笔记标签删除失败...');
    }

    /**
     * 添加或编辑笔记
     *
     * @RequestMapping(path="edit-article", methods="post")
     */
    public function editArticle()
    {
        $params = $this->request->all();
        $this->validate($params, [
            'article_id' => 'required|integer|min:0',
            'class_id' => 'required|integer|min:0',
            'title' => 'required|max:255',
            'content' => 'required',
            'md_content' => 'required'
        ]);

        $id = $this->articleService->editArticle($this->uid(), (int)$params['article_id'], [
            'title' => $params['title'],
            'abstract' => mb_substr(strip_tags($params['content']), 0, 200),
            'class_id' => $params['class_id'],
            'image' => get_html_images($params['content']),
            'md_content' => htmlspecialchars($params['md_content']),
            'content' => htmlspecialchars($params['content'])
        ]);

        return $id
            ? $this->response->success(['aid' => $id], '笔记编辑成功...')
            : $this->response->fail('笔记编辑失败...', ['id' => null]);
    }

    /**
     * 删除笔记
     *
     * @RequestMapping(path="delete-article", methods="post")
     */
    public function deleteArticle()
    {
        $params = $this->request->inputs(['article_id']);
        $this->validate($params, [
            'article_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->updateArticleStatus($this->uid(), (int)$params['article_id'], 2);

        return $isTrue
            ? $this->response->success([], '笔记删除成功...')
            : $this->response->fail('笔记删除失败...');
    }

    /**
     * 恢复删除笔记
     *
     * @RequestMapping(path="recover-article", methods="post")
     */
    public function recoverArticle()
    {
        $params = $this->request->inputs(['article_id']);
        $this->validate($params, [
            'article_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->updateArticleStatus($this->uid(), (int)$params['article_id'], 1);

        return $isTrue
            ? $this->response->success([], '笔记恢复成功...')
            : $this->response->fail('笔记恢复失败...');
    }

    /**
     * 笔记图片上传接口
     *
     * @RequestMapping(path="upload-article-image", methods="post")
     */
    public function uploadArticleImage()
    {
        $file = $this->request->file('image');
        if (!$file->isValid()) {
            return $this->response->fail();
        }

        $ext = $file->getExtension();
        if (!in_array($ext, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
            return $this->response->fail('图片格式错误，目前仅支持jpg、png、jpeg、gif和webp');
        }

        //获取图片信息
        $imgInfo = getimagesize($file->getRealPath());

        $path = $this->uploadService->media($file, 'media/images/notes/', create_image_name($ext, $imgInfo[0], $imgInfo[1]));
        if (!$path) {
            return $this->response->fail();
        }

        return $this->response->success([
            'save_path' => get_media_url($path)
        ]);
    }

    /**
     * 移动笔记至指定分类
     *
     * @RequestMapping(path="move-article", methods="post")
     */
    public function moveArticle()
    {
        $params = $this->request->inputs(['article_id', 'class_id']);
        $this->validate($params, [
            'article_id' => 'required|integer|min:0',
            'class_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->moveArticle(
            $this->uid(),
            $params['article_id'],
            $params['class_id']
        );

        return $isTrue
            ? $this->response->success([], '笔记移动成功...')
            : $this->response->fail('笔记移动失败...');
    }

    /**
     * 笔记标记星号接口
     *
     * @RequestMapping(path="set-asterisk-article", methods="post")
     */
    public function setAsteriskArticle()
    {
        $params = $this->request->inputs(['article_id', 'type']);
        $this->validate($params, [
            'article_id' => 'required|integer|min:0',
            'type' => 'required|in:1,2'
        ]);

        $isTrue = $this->articleService->setAsteriskArticle(
            $this->uid(),
            (int)$params['article_id'],
            (int)$params['type']
        );

        return $isTrue
            ? $this->response->success([], '笔记标记成功...')
            : $this->response->fail('笔记标记失败...');
    }

    /**
     * 更新笔记关联标签ID
     *
     * @RequestMapping(path="update-article-tag", methods="post")
     */
    public function updateArticleTag()
    {
        $params = $this->request->inputs(['article_id', 'tags']);
        $this->validate($params, [
            'article_id' => 'required|integer|min:0',
            'tags' => 'required|array'
        ]);

        $isTrue = $this->articleService->updateArticleTag($this->uid(), (int)$params['article_id'], $params['tags']);

        return $isTrue
            ? $this->response->success([], 'success...')
            : $this->response->fail('编辑失败...');
    }

    /**
     * 永久删除笔记文章
     *
     * @RequestMapping(path="forever-delete-article", methods="post")
     */
    public function foreverDelArticle()
    {
        $params = $this->request->inputs(['article_id']);
        $this->validate($params, [
            'article_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->foreverDelArticle($this->uid(), (int)$params['article_id']);

        return $isTrue
            ? $this->response->success([], '笔记删除成功...')
            : $this->response->fail('笔记删除失败...');
    }

    /**
     * 上传笔记附件
     *
     * @RequestMapping(path="upload-article-annex", methods="post")
     */
    public function uploadArticleAnnex()
    {
        $params = $this->request->inputs(['article_id']);
        $this->validate($params, [
            'article_id' => 'required|integer|min:0'
        ]);

        $file = $this->request->file('annex');
        if (!$file->isValid()) {
            return $this->response->fail('上传文件验证失败...');
        }

        $annex = [
            'file_suffix' => $file->getExtension(),
            'file_size' => $file->getSize(),
            'save_dir' => '',
            'original_name' => $file->getClientFilename()
        ];

        $path = $this->uploadService->media($file,
            'files/notes/' . date('Ymd'),
            "[{$annex['file_suffix']}]" . uniqid() . Str::random(16) . '.' . 'tmp'
        );

        if (!$path) {
            return $this->response->fail();
        }

        $annex['save_dir'] = $path;
        $annex['id'] = $this->articleService->insertArticleAnnex($this->uid(), (int)$params['article_id'], $annex);
        if (!$annex['id']) {
            return $this->response->fail('附件上传失败，请稍后再试...');
        }

        return $this->response->success($annex, '笔记附件上传成功...');
    }

    /**
     * 删除笔记附件
     *
     * @RequestMapping(path="delete-article-annex", methods="post")
     */
    public function deleteArticleAnnex()
    {
        $params = $this->request->inputs(['annex_id']);
        $this->validate($params, [
            'annex_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->updateArticleAnnexStatus($this->uid(), (int)$params['annex_id'], 2);

        return $isTrue
            ? $this->response->success([], '笔记附件删除成功...')
            : $this->response->fail('笔记附件删除失败...');
    }

    /**
     * 恢复笔记附件
     *
     * @RequestMapping(path="recover-article-annex", methods="post")
     */
    public function recoverArticleAnnex()
    {
        $params = $this->request->inputs(['annex_id']);
        $this->validate($params, [
            'annex_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->updateArticleAnnexStatus($this->uid(), (int)$params['annex_id'], 1);

        return $isTrue
            ? $this->response->success([], '笔记附件恢复成功...')
            : $this->response->fail('笔记附件恢复失败...');
    }

    /**
     * 获取附件回收站列表
     *
     * @RequestMapping(path="recover-annex-list", methods="get")
     */
    public function recoverAnnexList()
    {
        $rows = $this->articleService->recoverAnnexList($this->uid());
        if ($rows) {
            $getDay = function ($delete_at) {
                $last_time = strtotime('+30 days', strtotime($delete_at));

                return (time() > $last_time) ? 0 : diff_date(date('Y-m-d', $last_time), date('Y-m-d'));
            };

            array_walk($rows, function (&$item) use ($getDay) {
                $item['day'] = $getDay($item['deleted_at']);
                $item['visible'] = false;
            });
        }

        return $this->response->success(['rows' => $rows]);
    }

    /**
     * 永久删除笔记附件(从已删除附件中永久删除)
     *
     * @RequestMapping(path="forever-delete-annex", methods="post")
     */
    public function foreverDelAnnex()
    {
        $params = $this->request->inputs(['annex_id']);
        $this->validate($params, [
            'annex_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->foreverDelAnnex($this->uid(), (int)$params['annex_id']);

        return $isTrue
            ? $this->response->success([], '笔记附件删除成功...')
            : $this->response->fail('笔记附件删除失败...');
    }
}
