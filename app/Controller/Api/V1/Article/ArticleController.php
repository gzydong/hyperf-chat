<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Article;

use App\Controller\Api\V1\CController;
use App\Helper\StringHelper;
use App\Service\ArticleService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use League\Flysystem\Filesystem;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ArticleController
 *
 * @Controller(prefix="/api/v1/note/article")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1
 */
class ArticleController extends CController
{
    /**
     * @var ArticleService
     */
    private $articleService;

    public function __construct(ArticleService $service)
    {
        parent::__construct();

        $this->articleService = $service;
    }

    /**
     * 获取笔记列表
     *
     * @RequestMapping(path="list", methods="get")
     */
    public function getArticleList(): ResponseInterface
    {
        $params1 = $this->request->inputs(['keyword', 'find_type', 'cid', 'page']);

        $this->validate($params1, [
            // 搜索关键词
            'keyword'   => "present",
            // 查询类型 $findType 1:获取近期日记  2:获取星标日记  3:获取指定分类文章  4:获取指定标签文章 5:获取已删除文章 6:关键词搜索
            'find_type' => 'required|in:0,1,2,3,4,5,6',
            // 分类ID
            'cid'       => 'present|integer|min:-1',
            'page'      => 'present|integer|min:1'
        ]);

        $params              = [];
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
     * @RequestMapping(path="detail", methods="get")
     */
    public function detail(): ResponseInterface
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
     * 添加或编辑笔记
     *
     * @RequestMapping(path="editor", methods="post")
     */
    public function editor(): ResponseInterface
    {
        $params = $this->request->all();

        $this->validate($params, [
            'article_id' => 'required|integer|min:0',
            'class_id'   => 'required|integer|min:0',
            'title'      => 'required|max:255',
            'content'    => 'required',
            'md_content' => 'required'
        ]);

        $id = $this->articleService->editArticle($this->uid(), (int)$params['article_id'], [
            'title'      => $params['title'],
            'abstract'   => mb_substr(strip_tags($params['content']), 0, 200),
            'class_id'   => $params['class_id'],
            'image'      => StringHelper::getHtmlImage($params['content']),
            'md_content' => htmlspecialchars($params['md_content']),
            'content'    => htmlspecialchars($params['content'])
        ]);

        return $id ? $this->response->success(['id' => $id], '笔记编辑成功...') : $this->response->fail();
    }

    /**
     * 删除笔记
     *
     * @RequestMapping(path="delete", methods="post")
     */
    public function delete(): ResponseInterface
    {
        $params = $this->request->inputs(['article_id']);

        $this->validate($params, [
            'article_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->updateArticleStatus($this->uid(), (int)$params['article_id'], 2);

        return $isTrue ? $this->response->success() : $this->response->fail();
    }

    /**
     * 恢复删除笔记
     *
     * @RequestMapping(path="recover", methods="post")
     */
    public function recover(): ResponseInterface
    {
        $params = $this->request->inputs(['article_id']);

        $this->validate($params, [
            'article_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->updateArticleStatus($this->uid(), (int)$params['article_id'], 1);

        return $isTrue ? $this->response->success() : $this->response->fail();
    }

    /**
     * 笔记图片上传接口
     *
     * @RequestMapping(path="upload/image", methods="post")
     * @param Filesystem $filesystem
     * @return ResponseInterface
     */
    public function upload(Filesystem $filesystem): ResponseInterface
    {
        $file = $this->request->file('image');

        if (!$file || !$file->isValid()) {
            return $this->response->fail();
        }

        $ext = $file->getExtension();
        if (!in_array($ext, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
            return $this->response->fail('图片格式错误，目前仅支持jpg、png、jpeg、gif和webp');
        }

        try {
            $path = 'public/media/images/notes/' . date('Ymd') . '/' . create_image_name($ext, getimagesize($file->getRealPath()));
            $filesystem->write($path, file_get_contents($file->getRealPath()));
        } catch (\Exception $e) {
            return $this->response->fail();
        }

        return $this->response->success(['url' => get_media_url($path)]);
    }

    /**
     * 移动笔记至指定分类
     *
     * @RequestMapping(path="move", methods="post")
     */
    public function move(): ResponseInterface
    {
        $params = $this->request->inputs(['article_id', 'class_id']);

        $this->validate($params, [
            'article_id' => 'required|integer|min:0',
            'class_id'   => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->moveArticle($this->uid(), $params['article_id'], $params['class_id']);

        return $isTrue ? $this->response->success() : $this->response->fail();
    }

    /**
     * 笔记标记星号接口
     *
     * @RequestMapping(path="asterisk", methods="post")
     */
    public function asterisk(): ResponseInterface
    {
        $params = $this->request->inputs(['article_id', 'type']);

        $this->validate($params, [
            'article_id' => 'required|integer|min:0',
            'type'       => 'required|in:1,2'
        ]);

        $isTrue = $this->articleService->setAsteriskArticle($this->uid(), (int)$params['article_id'], (int)$params['type']);

        return $isTrue ? $this->response->success() : $this->response->fail();
    }

    /**
     * 更新笔记关联标签ID
     *
     * @RequestMapping(path="tag", methods="post")
     */
    public function tag(): ResponseInterface
    {
        $params = $this->request->inputs(['article_id', 'tags']);

        $this->validate($params, [
            'article_id' => 'required|integer|min:0',
            'tags'       => 'present|array'
        ]);

        $isTrue = $this->articleService->updateArticleTag($this->uid(), (int)$params['article_id'], $params['tags']);

        return $isTrue ? $this->response->success() : $this->response->fail();
    }

    /**
     * 永久删除笔记文章
     *
     * @RequestMapping(path="forever/delete", methods="post")
     * @return ResponseInterface
     * @throws \Exception
     */
    public function foreverDelete(): ResponseInterface
    {
        $params = $this->request->inputs(['article_id']);

        $this->validate($params, [
            'article_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->foreverDelArticle($this->uid(), (int)$params['article_id']);

        return $isTrue ? $this->response->success() : $this->response->fail();
    }

    /**
     * 笔记分类合并接口
     *
     * @RequestMapping(path="merge", methods="post")
     */
    public function merge(): ResponseInterface
    {
        $params = $this->request->inputs(['class_id', 'toid']);

        $this->validate($params, [
            'class_id' => 'required|integer',
            'toid'     => 'required|integer'
        ]);

        $isTrue = $this->articleService->mergeArticleClass($this->uid(), (int)$params['class_id'], (int)$params['toid']);

        return $isTrue ? $this->response->success() : $this->response->fail();
    }
}
