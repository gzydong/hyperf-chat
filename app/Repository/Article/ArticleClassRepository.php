<?php
declare(strict_types=1);

namespace App\Repository\Article;

use App\Model\Article\Article;
use App\Model\Article\ArticleClass;
use App\Repository\BaseRepository;
use Hyperf\DbConnection\Db;

class ArticleClassRepository extends BaseRepository
{
    public function __construct(ArticleClass $articleClass)
    {
        parent::__construct($articleClass);
    }

    /**
     * 获取用户分类
     *
     * @param int $user_id 用户ID
     *
     * @return array
     */
    public function getUserClass(int $user_id): array
    {
        $items = $this->get(["user_id" => $user_id, 'order by' => ['sort' => 'asc']], ['id', 'class_name', 'is_default']);
        if (empty($items)) {
            return [];
        }

        $rows = Article::select(['class_id', Db::raw('count(class_id) as count')])->where('user_id', $user_id)->where('status', 1)->groupBy(['class_id'])->get()->keyBy("class_id")->toArray();
        foreach ($items as $k => $val) {
            $items[$k]['count'] = isset($rows[$val['id']]) ? $rows[$val['id']]['count'] : 0;
        }

        return $items;
    }
}
