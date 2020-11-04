<?php

namespace App\Service;


use App\Model\Article;
use App\Model\ArticleClass;
use App\Model\ArticleTag;
use App\Model\ArticleAnnex;
use App\Traits\PagingTrait;
use Hyperf\DbConnection\Db;

class ArticleService extends BaseService
{
    use PagingTrait;

    /**
     * 获取用户文章分类列表
     *
     * @param int $user_id 用户ID
     * @return array
     */
    public function getUserClass(int $user_id)
    {
        $subJoin = Article::select('class_id', Db::raw('count(class_id) as count'))->where('user_id', $user_id)->where('status', 1)->groupBy('class_id');

        return ArticleClass::leftJoinSub($subJoin, 'sub_join', function ($join) {
            $join->on('article_class.id', '=', Db::raw('sub_join.class_id'));
        })->where('article_class.user_id', $user_id)
            ->orderBy('article_class.sort', 'asc')
            ->get(['article_class.id', 'article_class.class_name', 'article_class.is_default', Db::raw('sub_join.count')])
            ->toArray();
    }

    /**
     * 获取用户文章标签列表
     *
     * @param int $user_id 用户ID
     * @return mixed
     */
    public function getUserTags(int $user_id)
    {
        $items = ArticleTag::where('user_id', $user_id)->orderBy('id', 'desc')->get(['id', 'tag_name'])->toArray();
        array_walk($items, function ($item) use ($user_id) {
            $item['count'] = Article::where('user_id', $user_id)->whereRaw("FIND_IN_SET({$item['id']},tags_id)")->count();
        });

        return $items;
    }

    /**
     * 获取用户文章列表
     *
     * @param int $user_id 用户ID
     * @param int $page 分页
     * @param int $page_size 分页大小
     * @param array $params 查询参数
     * @return array
     */
    public function getUserArticleList(int $user_id, int $page, int $page_size, $params = [])
    {
        $filed = ['article.id', 'article.class_id', 'article.title', 'article.image', 'article.abstract', 'article.updated_at', 'article_class.class_name', 'article.status'];

        $countSqlObj = Article::select();
        $rowsSqlObj = Article::select($filed)
            ->leftJoin('article_class', 'article_class.id', '=', 'article.class_id');

        $countSqlObj->where('article.user_id', $user_id);
        $rowsSqlObj->where('article.user_id', $user_id);

        if ($params['find_type'] == 3) {
            $countSqlObj->where('article.class_id', $params['class_id']);
            $rowsSqlObj->where('article.class_id', $params['class_id']);
        } else if ($params['find_type'] == 4) {
            $countSqlObj->whereRaw("FIND_IN_SET({$params['class_id']},tags_id)");
            $rowsSqlObj->whereRaw("FIND_IN_SET({$params['class_id']},tags_id)");
        } else if ($params['find_type'] == 2) {
            $countSqlObj->where('article.is_asterisk', 1);
            $rowsSqlObj->where('article.is_asterisk', 1);
        }

        $countSqlObj->where('article.status', $params['find_type'] == 5 ? 2 : 1);
        $rowsSqlObj->where('article.status', $params['find_type'] == 5 ? 2 : 1);

        if (isset($params['keyword'])) {
            $countSqlObj->where('article.title', 'like', "%{$params['keyword']}%");
            $rowsSqlObj->where('article.title', 'like', "%{$params['keyword']}%");
        }

        $count = $countSqlObj->count();
        $rows = [];
        if ($count > 0) {
            if ($params['find_type'] == 1) {
                $rowsSqlObj->orderBy('updated_at', 'desc');
                $page_size = 20;
            } else {
                $rowsSqlObj->orderBy('id', 'desc');
            }

            $rows = $rowsSqlObj->forPage($page, $page_size)->get()->toArray();
        }

        return $this->getPagingRows($rows, $count, $page, $page_size);
    }

    /**
     * 获取文章详情
     *
     * @param int $article_id 文章ID
     * @param int $user_id 用户ID
     * @return array
     */
    public function getArticleDetail(int $article_id, $user_id = 0)
    {
        $info = Article::where('id', $article_id)->where('user_id', $user_id)->first(['id', 'class_id', 'tags_id', 'title', 'status', 'is_asterisk', 'created_at', 'updated_at']);
        if (!$info) {
            return [];
        }

        // 关联文章详情
        $detail = $info->detail;
        if (!$detail) {
            return [];
        }

        $tags = [];
        if ($info->tags_id) {
            $tags = ArticleTag::whereIn('id', explode(',', $info->tags_id))->get(['id', 'tag_name']);
        }

        return [
            'id' => $article_id,
            'class_id' => $info->class_id,
            'title' => $info->title,
            'md_content' => htmlspecialchars_decode($detail->md_content),
            'content' => htmlspecialchars_decode($detail->content),
            'is_asterisk' => $info->is_asterisk,
            'status' => $info->status,
            'created_at' => $info->created_at,
            'updated_at' => $info->updated_at,
            'tags' => $tags,
            'files' => $this->findArticleAnnexAll($user_id, $article_id)
        ];
    }

    /**
     * 获取笔记附件
     *
     * @param int $user_id 用户ID
     * @param int $article_id 笔记ID
     * @return mixed
     */
    public function findArticleAnnexAll(int $user_id, int $article_id)
    {
        return ArticleAnnex::where([
            ['user_id', '=', $user_id],
            ['article_id', '=', $article_id],
            ['status', '=', 1]
        ])->get(['id', 'file_suffix', 'file_size', 'original_name', 'created_at'])->toArray();
    }

}
