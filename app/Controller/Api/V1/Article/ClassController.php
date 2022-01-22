<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Article;

use App\Cache\Repository\LockRedis;
use App\Controller\Api\V1\CController;
use App\Repository\Article\ArticleClassRepository;
use App\Service\ArticleService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\JWTAuthMiddleware;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ClassController
 *
 * @Controller(prefix="/api/v1/note/class")
 * @Middleware(JWTAuthMiddleware::class)
 *
 * @package App\Controller\Api\V1\Article
 */
class ClassController extends CController
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
     * 获取笔记分类列表
     *
     * @RequestMapping(path="list", methods="get")
     */
    public function list(): ResponseInterface
    {
        $rows = di()->get(ArticleClassRepository::class)->getUserClass($this->uid());

        return $this->response->success(['rows' => $rows]);
    }

    /**
     * 编辑分类
     *
     * @RequestMapping(path="editor", methods="post")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function editor(): ResponseInterface
    {
        $params = $this->request->inputs(['class_id', 'class_name']);

        $this->validate($params, [
            'class_id'   => 'required|integer',
            'class_name' => 'required|max:20'
        ]);

        $class_id = $this->articleService->editArticleClass($this->uid(), $params['class_id'], $params['class_name']);
        if (!$class_id) {
            return $this->response->fail('笔记分类编辑失败！');
        }

        return $this->response->success(['id' => $class_id]);
    }

    /**
     * 删除分类
     *
     * @RequestMapping(path="delete", methods="post")
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function delete(): ResponseInterface
    {
        $params = $this->request->inputs(['class_id']);

        $this->validate($params, [
            'class_id' => 'required|integer'
        ]);

        $isTrue = $this->articleService->delArticleClass($this->uid(), (int)$params['class_id']);

        return $isTrue ? $this->response->success() : $this->response->fail();
    }

    /**
     * 笔记分类列表排序接口
     *
     * @RequestMapping(path="sort", methods="post")
     * @return ResponseInterface
     * @throws \Exception
     */
    public function sort(): ResponseInterface
    {
        $params = $this->request->inputs(['class_id', 'sort_type']);

        $this->validate($params, [
            'class_id'  => 'required|integer',
            'sort_type' => 'required|in:1,2'
        ]);

        $lockKey = "article:sort_{$params['class_id']}_{$params['sort_type']}";

        $lock = LockRedis::getInstance();

        if ($lock->lock($lockKey, 3, 500)) {
            $isTrue = $this->articleService->articleClassSort($this->uid(), (int)$params['class_id'], (int)$params['sort_type']);

            $lock->delete($lockKey);
        } else {
            $isTrue = false;
        }

        return $isTrue ? $this->response->success() : $this->response->fail();
    }
}
