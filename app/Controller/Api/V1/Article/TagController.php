<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Article;

use App\Controller\Api\V1\CController;
use App\Service\ArticleService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use Psr\Http\Message\ResponseInterface;

/**
 * Class TagController
 *
 * @Controller(prefix="/api/v1/note/tag")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1\Article
 */
class TagController extends CController
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
     * 获取标签列表
     *
     * @RequestMapping(path="list", methods="get")
     */
    public function list(): ResponseInterface
    {
        return $this->response->success([
            'tags' => $this->articleService->getUserTags($this->uid())
        ]);
    }

    /**
     * 编辑标签
     *
     * @RequestMapping(path="editor", methods="post")
     */
    public function editor(): ResponseInterface
    {
        $params = $this->request->inputs(['tag_id', 'tag_name']);

        $this->validate($params, [
            'tag_id'   => 'required|integer|min:0',
            'tag_name' => 'required|max:20'
        ]);

        $id = $this->articleService->editArticleTag($this->uid(), (int)$params['tag_id'], $params['tag_name']);

        return $id ? $this->response->success(['id' => $id]) : $this->response->fail();
    }

    /**
     * 删除标签
     *
     * @RequestMapping(path="delete", methods="post")
     */
    public function delete(): ResponseInterface
    {
        $params = $this->request->inputs(['tag_id']);

        $this->validate($params, [
            'tag_id' => 'required|integer|min:0'
        ]);

        $isTrue = $this->articleService->delArticleTags($this->uid(), (int)$params['tag_id']);

        return $isTrue ? $this->response->success() : $this->response->fail();
    }
}
