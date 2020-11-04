<?php

namespace App\Controller\Api\V1;

use App\Service\ArticleService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Phper666\JWTAuth\Middleware\JWTAuthMiddleware;

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
     * 获取笔记分类列表
     *
     * @RequestMapping(path="article-class", methods="get")
     */
    public function getArticleClass()
    {
        $user_id = $this->uid();

        return $this->response->success(
            $this->articleService->getUserClass($user_id)
        );
    }

    /**
     * 获取笔记标签列表
     *
     * @RequestMapping(path="article-tags", methods="get")
     */
    public function getArticleTags()
    {
        $user_id = $this->uid();

        return $this->response->success(
            $this->articleService->getUserTags($user_id)
        );
    }

    /**
     * 获取笔记列表
     *
     * @RequestMapping(path="article-list", methods="get")
     */
    public function getArticleList()
    {
        $this->validate($this->request->all(), [
            'keyword' => "present",
            'find_type' => 'required|in:0,1,2,3,4,5,6',
            'cid' => 'present|integer|min:-1',
            'page' => 'present|integer|min:1'
        ]);

        // 查询类型 $findType 1:获取近期日记  2:获取星标日记  3:获取指定分类文章  4:获取指定标签文章 5:获取已删除文章 6:关键词搜索
        $findType = $this->request->input('find_type', 0);
        $keyword = $this->request->input('keyword', '');// 搜索关键词
        $cid = $this->request->input('cid', -1);// 分类ID
        $page = $this->request->input('page', 1);
        $user_id = $this->uid();

        $params = [];
        $params['find_type'] = $findType;
        if (in_array($findType, [3, 4])) {
            $params['class_id'] = $cid;
        }

        if (!empty($keyword)) {
            $params['keyword'] = $keyword;
        }

        return $this->response->success(
            $this->articleService->getUserArticleList($user_id, $page, 10000, $params)
        );
    }

    /**
     * 获取笔记详情
     *
     * @RequestMapping(path="article-detail", methods="get")
     */
    public function getArticleDetail()
    {
        $this->validate($this->request->all(), [
            'article_id' => 'required|integer',
        ]);

        $data = $this->articleService->getArticleDetail(
            $this->request->input('article_id'),
            $this->uid()
        );

        return $this->response->success($data);
    }

    /**
     * 添加或编辑笔记分类
     *
     * @RequestMapping(path="edit-article-class", methods="post")
     */
    public function editArticleClass()
    {

    }

    /**
     * 删除笔记分类
     *
     * @RequestMapping(path="del-article-class", methods="post")
     */
    public function delArticleClass()
    {

    }

    /**
     * 笔记分类列表排序接口
     *
     * @RequestMapping(path="article-class-sort", methods="post")
     */
    public function articleClassSort()
    {

    }

    /**
     * 笔记分类合并接口
     *
     * @RequestMapping(path="merge-article-class", methods="post")
     */
    public function mergeArticleClass()
    {

    }

    /**
     * 添加或编辑笔记标签
     *
     * @RequestMapping(path="edit-article-tag", methods="post")
     */
    public function editArticleTags()
    {

    }

    /**
     * 删除笔记标签
     *
     * @RequestMapping(path="del-article-tag", methods="post")
     */
    public function delArticleTags()
    {

    }

    /**
     * 编辑笔记信息
     *
     * @RequestMapping(path="edit-article", methods="post")
     */
    public function editArticle()
    {

    }

    /**
     * 删除笔记
     *
     * @RequestMapping(path="delete-article", methods="post")
     */
    public function deleteArticle()
    {

    }

    /**
     * 恢复笔记
     *
     * @RequestMapping(path="recover-article", methods="post")
     */
    public function recoverArticle()
    {

    }

    /**
     * 笔记图片上传接口
     *
     * @RequestMapping(path="upload-article-image", methods="post")
     */
    public function uploadArticleImage()
    {

    }


    /**
     * 移动笔记至指定分类
     *
     * @RequestMapping(path="move-article", methods="post")
     */
    public function moveArticle()
    {

    }

    /**
     * 笔记标记星号接口
     *
     * @RequestMapping(path="set-asterisk-article", methods="post")
     */
    public function setAsteriskArticle()
    {

    }

    /**
     * 更新笔记关联标签ID
     *
     * @RequestMapping(path="update-article-tag", methods="post")
     */
    public function updateArticleTag()
    {

    }

    /**
     * 永久删除笔记文章
     *
     * @RequestMapping(path="forever-delete-article", methods="post")
     */
    public function foreverDelArticle()
    {

    }

    /**
     * 上传笔记附件
     *
     * @RequestMapping(path="upload-article-annex", methods="post")
     */
    public function uploadArticleAnnex()
    {

    }

    /**
     * 删除笔记附件
     *
     * @RequestMapping(path="delete-article-annex", methods="post")
     */
    public function deleteArticleAnnex()
    {

    }

    /**
     * 恢复笔记附件
     *
     * @RequestMapping(path="recover-article-annex", methods="post")
     */
    public function recoverArticleAnnex()
    {

    }

    /**
     * 获取附件回收站列表
     *
     * @RequestMapping(path="recover-annex-list", methods="get")
     */
    public function recoverAnnexList()
    {

    }

    /**
     * 永久删除笔记附件(从已删除附件中永久删除)
     *
     * @RequestMapping(path="forever-delete-annex", methods="get")
     */
    public function foreverDelAnnex()
    {

    }
}
