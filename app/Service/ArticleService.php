<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Article\Article;
use App\Model\Article\ArticleClass;
use App\Model\Article\ArticleDetail;
use App\Model\Article\ArticleTag;
use App\Model\Article\ArticleAnnex;
use App\Traits\PagingTrait;
use Hyperf\DbConnection\Db;
use Exception;

/**
 * 笔记服务层
 *
 * @package App\Service
 */
class ArticleService extends BaseService
{
    use PagingTrait;

    /**
     * 获取用户文章标签列表
     *
     * @param int $user_id 用户ID
     * @return array
     */
    public function getUserTags(int $user_id): array
    {
        $items = ArticleTag::where('user_id', $user_id)->orderBy('id', 'desc')->get(['id', 'tag_name', Db::raw('0 as count')])->toArray();
        foreach ($items as $k => $item) {
            $items[$k]['count'] = Article::where('user_id', $user_id)->whereRaw("FIND_IN_SET({$item['id']},tags_id)")->count();
        }

        return $items;
    }

    /**
     * 获取用户文章列表
     *
     * @param int   $user_id   用户ID
     * @param int   $page      分页
     * @param int   $page_size 分页大小
     * @param array $params    查询参数
     * @return array
     */
    public function getUserArticleList(int $user_id, int $page, int $page_size, $params = []): array
    {
        $filed = ['article.id', 'article.class_id', 'article.title', 'article.image', 'article.abstract', 'article.updated_at', 'article_class.class_name', 'article.status'];

        $model = Article::select($filed)
            ->leftJoin('article_class', 'article_class.id', '=', 'article.class_id');

        $model->where('article.user_id', $user_id);

        if ($params['find_type'] == 3) {
            $model->where('article.class_id', $params['class_id']);
        } else if ($params['find_type'] == 4) {
            $model->whereRaw("FIND_IN_SET({$params['class_id']},tags_id)");
        } else if ($params['find_type'] == 2) {
            $model->where('article.is_asterisk', 1);
        }

        $model->where('article.status', $params['find_type'] == 5 ? 2 : 1);

        if (isset($params['keyword']) && !empty($params['keyword'])) {
            $model->where('article.title', 'like', "%{$params['keyword']}%");
        }

        $count = $model->count();
        $rows  = [];
        if ($count > 0) {
            if ($params['find_type'] == 1) {
                $model->orderBy('updated_at', 'desc');
                $page_size = 20;
            } else {
                $model->orderBy('id', 'desc');
            }

            $rows = $model->forPage($page, $page_size)->get()->toArray();
        }

        return $this->getPagingRows($rows, $count, $page, $page_size);
    }

    /**
     * 获取文章详情
     *
     * @param int $article_id 文章ID
     * @param int $user_id    用户ID
     * @return array
     */
    public function getArticleDetail(int $article_id, $user_id = 0): array
    {
        $info = Article::where('id', $article_id)->where('user_id', $user_id)->first(['id', 'class_id', 'tags_id', 'title', 'status', 'is_asterisk', 'created_at', 'updated_at']);

        if (!$info) return [];

        // 关联文章详情
        $detail = $info->detail;
        if (!$detail) return [];

        $tags = [];
        if ($info->tags_id) {
            $tags = ArticleTag::whereIn('id', explode(',', $info->tags_id))->get(['id', 'tag_name']);
        }

        return [
            'id'          => $article_id,
            'class_id'    => $info->class_id,
            'title'       => $info->title,
            'md_content'  => htmlspecialchars_decode($detail->md_content),
            'content'     => htmlspecialchars_decode($detail->content),
            'is_asterisk' => $info->is_asterisk,
            'status'      => $info->status,
            'created_at'  => $info->created_at,
            'updated_at'  => $info->updated_at,
            'tags'        => $tags,
            'files'       => $this->findArticleAnnexAll($user_id, $article_id)
        ];
    }

    /**
     * 获取笔记附件
     *
     * @param int $user_id    用户ID
     * @param int $article_id 笔记ID
     * @return array
     */
    public function findArticleAnnexAll(int $user_id, int $article_id): array
    {
        return ArticleAnnex::where([
            ['user_id', '=', $user_id],
            ['article_id', '=', $article_id],
            ['status', '=', 1]
        ])->get(['id', 'suffix', 'size', 'original_name', 'created_at'])->toArray();
    }

    /**
     * 编辑笔记分类
     *
     * @param int        $uid        用户ID
     * @param int|string $class_id   分类ID
     * @param string     $class_name 分类名
     * @return bool|int
     */
    public function editArticleClass(int $uid, $class_id, string $class_name)
    {
        if ($class_id) {
            if (!ArticleClass::where('id', $class_id)->where('user_id', $uid)->where('is_default', 0)->update(['class_name' => $class_name])) {
                return false;
            }

            return $class_id;
        }

        $arr   = [];
        $items = ArticleClass::where('user_id', $uid)->get(['id', 'sort']);
        foreach ($items as $key => $item) {
            $arr[] = ['id' => $item->id, 'sort' => $key + 2];
        }

        unset($items);

        Db::beginTransaction();
        try {
            foreach ($arr as $val) {
                ArticleClass::where('id', $val['id'])->update(['sort' => $val['sort']]);
            }

            $res = ArticleClass::create(['user_id' => $uid, 'class_name' => $class_name, 'sort' => 1, 'created_at' => date("y-m-d H:i:s")]);

            Db::commit();
            return $res->id;
        } catch (Exception $e) {
            Db::rollBack();

            logger()->info("编辑标签失败", [
                "error" => $e->getMessage(),
                "line"  => $e->getLine(),
                "file"  => $e->getFile(),
            ]);
            return false;
        }
    }

    /**
     * 删除笔记分类
     *
     * @param int $user_id  用户ID
     * @param int $class_id 分类ID
     * @return bool
     * @throws Exception
     */
    public function delArticleClass(int $user_id, int $class_id): bool
    {
        $result = ArticleClass::where('id', $class_id)->where('user_id', $user_id)->first(['id', 'sort']);
        if (!$result) return false;

        $count = Article::where('user_id', $user_id)->where('class_id', $class_id)->count();
        if ($count > 0) return false;

        Db::transaction(function () use ($user_id, $class_id, $result) {
            ArticleClass::where('id', $class_id)->where('user_id', $user_id)->where('is_default', 0)->delete();
            ArticleClass::where('user_id', $user_id)->where('sort', '>', $result->sort)->decrement('sort');
        });

        return true;
    }

    /**
     * 文集分类排序
     *
     * @param int $user_id   用户ID
     * @param int $class_id  文集分类ID
     * @param int $sort_type 排序方式
     * @return bool
     */
    public function articleClassSort(int $user_id, int $class_id, int $sort_type): bool
    {
        if (!$info = ArticleClass::select(['id', 'sort'])->where('id', $class_id)->where('user_id', $user_id)->first()) {
            return false;
        }

        // 向下排序
        if ($sort_type == 1) {
            $maxSort = ArticleClass::where('user_id', $user_id)->max('sort');
            if ($maxSort == $info->sort) {
                return false;
            }

            DB::beginTransaction();
            try {
                ArticleClass::where('user_id', $user_id)->where('sort', $info->sort + 1)->update([
                    'sort' => $info->sort
                ]);

                ArticleClass::where('id', $class_id)->update([
                    'sort' => $info->sort + 1
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return false;
            }

            return true;
        } else if ($sort_type == 2) {//向上排序
            $minSort = ArticleClass::where('user_id', $user_id)->min('sort');
            if ($minSort == $info->sort) {
                return false;
            }

            DB::beginTransaction();
            try {
                ArticleClass::where('user_id', $user_id)->where('sort', $info->sort - 1)->update([
                    'sort' => $info->sort
                ]);

                ArticleClass::where('id', $class_id)->where('user_id', $user_id)->update([
                    'sort' => $info->sort - 1
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return false;
            }

            return true;
        }
    }

    /**
     * 笔记分类合并
     *
     * @param int $user_id     用户ID
     * @param int $class_id    笔记分类ID
     * @param int $to_class_id 笔记分类ID
     * @return bool
     */
    public function mergeArticleClass(int $user_id, int $class_id, int $to_class_id): bool
    {
        $count = ArticleClass::whereIn('id', [$class_id, $to_class_id])->where('user_id', $user_id)->count();
        if ($count < 2) {
            return false;
        }

        return (bool)Article::where('class_id', $class_id)->where('user_id', $user_id)->update([
            'class_id'   => $to_class_id,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 编辑笔记标签
     *
     * @param int    $uid      用户ID
     * @param int    $tag_id   标签ID
     * @param string $tag_name 标签名
     * @return bool|int
     */
    public function editArticleTag(int $uid, int $tag_id, string $tag_name)
    {
        $id = ArticleTag::where('user_id', $uid)->where('tag_name', $tag_name)->value('id');
        if ($tag_id) {
            if ($id && $id != $tag_id) {
                return false;
            }

            return ArticleTag::where('id', $tag_id)->where('user_id', $uid)->update(['tag_name' => $tag_name]) ? $tag_id : false;
        } else {
            // 判断新添加的标签名是否存在
            if ($id) return false;

            $insRes = ArticleTag::create(['user_id' => $uid, 'tag_name' => $tag_name, 'sort' => 1]);
            if (!$insRes) {
                return false;
            }

            return $insRes->id;
        }
    }

    /**
     * 删除笔记标签
     *
     * @param int $uid    用户ID
     * @param int $tag_id 标签ID
     * @return bool
     */
    public function delArticleTags(int $uid, int $tag_id): bool
    {
        if (!ArticleTag::where('id', $tag_id)->where('user_id', $uid)->exists()) {
            return false;
        }

        $count = Article::where('user_id', $uid)->whereRaw("FIND_IN_SET({$tag_id},tags_id)")->count();
        if ($count > 0) return false;

        try {
            return (bool)ArticleTag::where('id', $tag_id)->where('user_id', $uid)->delete();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 编辑文章信息
     *
     * @param int   $user_id    用户ID
     * @param int   $article_id 文章ID
     * @param array $data       文章数据
     * @return bool
     */
    public function editArticle(int $user_id, int $article_id, $data = [])
    {
        if ($article_id) {
            if (!Article::where('id', $article_id)->where('user_id', $user_id)->first()) {
                return false;
            }

            Db::beginTransaction();
            try {
                Article::where('id', $article_id)->where('user_id', $user_id)->update([
                    'class_id'   => $data['class_id'],
                    'title'      => $data['title'],
                    'abstract'   => $data['abstract'],
                    'image'      => $data['image'] ? $data['image'][0] : '',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                ArticleDetail::where('article_id', $article_id)->update([
                    'md_content' => $data['md_content'],
                    'content'    => $data['content']
                ]);

                Db::commit();
                return $article_id;
            } catch (Exception $e) {
                Db::rollBack();
            }

            return false;
        }

        Db::beginTransaction();
        try {
            $res = Article::create([
                'user_id'    => $user_id,
                'class_id'   => $data['class_id'],
                'title'      => $data['title'],
                'abstract'   => $data['abstract'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            ArticleDetail::create([
                'article_id' => $res->id,
                'md_content' => $data['md_content'],
                'content'    => $data['content']
            ]);

            Db::commit();
            return $res->id;
        } catch (Exception $e) {
            Db::rollBack();
        }

        return false;
    }

    /**
     * 更新笔记状态
     *
     * @param int $user_id    用户ID
     * @param int $article_id 笔记ID
     * @param int $status     笔记状态 1:正常 2:已删除
     * @return bool
     */
    public function updateArticleStatus(int $user_id, int $article_id, int $status): bool
    {
        $data = [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($status == 2) {
            $data['deleted_at'] = date('Y-m-d H:i:s');
        }

        return (bool)Article::where('id', $article_id)->where('user_id', $user_id)->update($data);
    }

    /**
     * 笔记移动至指定分类
     *
     * @param int $user_id    用户ID
     * @param int $article_id 笔记ID
     * @param int $class_id   笔记分类ID
     * @return bool
     */
    public function moveArticle(int $user_id, int $article_id, int $class_id): bool
    {
        return (bool)Article::where('id', $article_id)->where('user_id', $user_id)->update([
            'class_id'   => $class_id,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 笔记标记星号
     *
     * @param int $user_id    用户ID
     * @param int $article_id 笔记ID
     * @param int $type       1:标记星号 2:取消星号标记
     * @return bool
     */
    public function setAsteriskArticle(int $user_id, int $article_id, int $type): bool
    {
        return (bool)Article::where('id', $article_id)->where('user_id', $user_id)->update([
            'is_asterisk' => $type == 1 ? 1 : 0,
            'updated_at'  => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 更新笔记关联标签
     *
     * @param int   $uid        用户ID
     * @param int   $article_id 笔记ID
     * @param array $tags       关联标签ID
     * @return bool
     */
    public function updateArticleTag(int $uid, int $article_id, array $tags): bool
    {
        return (bool)Article::where('id', $article_id)->where('user_id', $uid)->update([
            'tags_id'    => implode(',', $tags),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 永久删除回收站中的笔记
     *
     * @param int $uid        用户ID
     * @param int $article_id 笔记ID
     * @return bool|int|mixed|null
     * @throws Exception
     */
    public function foreverDelArticle(int $uid, int $article_id)
    {
        $info = Article::where('id', $article_id)->where('user_id', $uid)->where('status', 2)->first(['id', 'title']);
        if (!$info) {
            return false;
        }

        $annex_files = $info->annexs()->get(['id', 'article_id', 'path'])->toArray();

        //判断笔记是否存在附件，不存在直接删除
        if (count($annex_files) == 0) {
            return $info->delete();
        }

        Db::beginTransaction();
        try {
            $info->detail->delete();

            if (!$info->delete()) {
                throw new Exception('删除笔记失败...');
            }

            if (!ArticleAnnex::whereIn('id', array_column($annex_files, 'id'))->delete()) {
                throw new Exception('删除笔记附件失败...');
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            return false;
        }

        // 从磁盘中永久删除文件附件
        //foreach ($annex_files as $item) {
        //Storage::disk('uploads')->delete($item['save_dir']);
        //}

        return true;
    }

    /**
     * 更新笔记附件状态
     *
     * @param int $user_id  用户ID
     * @param int $annex_id 附件ID
     * @param int $status   附件状态 1:正常 2:已删除
     * @return bool
     */
    public function updateArticleAnnexStatus(int $user_id, int $annex_id, int $status): bool
    {
        $data = ['status' => $status];
        if ($status == 2) {
            $data['deleted_at'] = date('Y-m-d H:i:s');
        }

        return (bool)ArticleAnnex::where('id', $annex_id)->where('user_id', $user_id)->update($data);
    }

    /**
     * 回收站附件列表
     *
     * @param int $uid 用户ID
     * @return array
     */
    public function recoverAnnexList(int $uid): array
    {
        return ArticleAnnex::join('article', 'article.id', '=', 'article_annex.article_id')
            ->where('article_annex.user_id', $uid)
            ->where('article_annex.status', 2)
            ->get([
                'article_annex.id',
                'article_annex.article_id',
                'article.title',
                'article_annex.original_name',
                'article_annex.deleted_at'
            ])->toArray();
    }

    /**
     * 永久删除笔记附件(从磁盘中永久删除)
     *
     * @param int $uid      用户ID
     * @param int $annex_id 笔记附件ID
     * @return mixed
     * @throws Exception
     */
    public function foreverDelAnnex(int $uid, int $annex_id)
    {
        $info = ArticleAnnex::where('id', $annex_id)->where('user_id', $uid)->where('status', 2)->first(['id', 'path']);
        if (!$info) {
            return false;
        }

        return $info->delete();
    }

    /**
     * 添加笔记附件
     *
     * @param int   $user_id    用户id
     * @param int   $article_id 笔记ID
     * @param array $annex      笔记附件信息
     * @return bool|int
     */
    public function insertArticleAnnex(int $user_id, int $article_id, array $annex)
    {
        if (!Article::where('id', $article_id)->where('user_id', $user_id)->exists()) {
            return false;
        }

        $result = ArticleAnnex::create([
            'user_id'       => $user_id,
            'article_id'    => $article_id,
            'suffix'        => $annex['suffix'],
            'size'          => $annex['size'],
            'drive'         => $annex['drive'],
            'path'          => $annex['path'],
            'original_name' => $annex['original_name'],
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        return $result ? $result->id : false;
    }
}
