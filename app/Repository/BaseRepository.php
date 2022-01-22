<?php
declare(strict_types=1);

namespace App\Repository;

use App\Traits\RepositoryTrait;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Builder as ModelBuilder;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Collection;

/**
 * Class BaseRepository 基础 Repository 类
 *
 * @method Model create(array $values) 新增数据
 * @method boolean insert(array $values) 新增数据
 * @method int|mixed insertGetId(array $values) 新增数据并获取新增ID
 * @method Model firstOrCreate(array $attributes, array $value = []) 查询数据没有就创建
 * @method Model firstOrNew(array $attributes, array $value = []) 查询数据没有就实例化
 * @method Model updateOrCreate(array $attributes, array $value = []) 查询修改没有就创建
 * @method Model updateOrInsert(array $attributes, array $values = []) 查询修改没有就实例化
 * @method Model find(int $id, array $fields = ['*']) 主键查询
 * @method Model findOrFail(int $id, array $fields = ['*']) 主键查询没有就抛出错误
 * @method Model findOrNew(int $id, array $fields = ['*']) 主键查询没有就实例化
 *
 * @method int count(array $where = [], string $field = '*') 统计数量
 * @method int|mixed max(array $where, string $field) 统计求最大值
 * @method int|mixed min(array $where, string $field) 统计求最小值
 * @method int|mixed avg(array $where, string $field) 统计求平均值
 * @method int|mixed sum(array $where, string $field) 统计求和
 *
 * @method int increment(array $where, string $field, $amount = 1, array $extra = []) 按查询条件指定字段递增指定值(默认递增1)
 * @method int decrement(array $where, string $field, $amount = 1, array $extra = []) 按查询条件指定字段递减指定值(默认递减1)
 *
 * @method string|int|null value(array $where, string $field) 按查询条件获取一行指定字段的数据
 * @method Collection pluck(array $where, string $field) 按查询条件获取多行指定字段
 * @method bool exists(array $where) 判断是否存在相关数据
 * @method bool doesntExist(array $where) 判断是否不存在相关数据
 *
 * @todo    待完善
 *
 * @package App\Repository
 */
abstract class BaseRepository
{
    use RepositoryTrait;

    /**
     * 获取单例
     *
     * @return static
     */
    public static function getInstance()
    {
        return di()->get(static::class);
    }

    /**
     * 查询单条数据
     *
     * @param array    $where    查询条件
     * @param string[] $fields   查询字段
     * @param bool     $is_array 是否返回数组格式
     * @return ModelBuilder|Model|object|array|null
     */
    final public function first(array $where = [], array $fields = ['*'], bool $is_array = false)
    {
        $this->handleField($fields);

        $data = $this->buildWhere($where)->first($fields);

        if ($is_array) {
            return $data ? $data->toArray() : [];
        }

        return $data;
    }

    /**
     * 查询多条数据
     *
     * @param array    $where  查询条件
     * @param string[] $fields 查询字段
     * @param array    $limit  limit 条件 [offset,limit]
     * @return array
     */
    final public function get(array $where = [], array $fields = ['*'], array $limit = []): array
    {
        $this->handleField($fields);

        $model = $this->buildWhere($where);

        if ($limit && count($limit) == 2) {
            $model->offset($limit[0] ?? 0)->limit($limit[1] ?? 0);
        }

        return $model->get($fields)->toArray();
    }

    /**
     * 查询分页数据
     *
     * @param array $where  查询条件
     * @param array $fields 查询字段
     * @param int   $page   当前页
     * @param int   $size   每页条数
     * @return array
     */
    final public function paginate(array $where, array $fields = ['*'], int $page = 1, int $size = 15): array
    {
        $this->handleField($fields);

        $model = $this->buildWhere($where);

        return toPaginate($model, $fields, $page, $size);
    }

    /**
     * 根据条件更新数据
     *
     * @param array $where  查询条件
     * @param array $values 更新字段
     * @return int
     */
    final public function update(array $where, array $values): int
    {
        return $this->buildWhere($where)->update($values);
    }

    /**
     * 根据条件批量更新数据
     *
     * @param array $where  查询条件
     * @param array $values 更新字段
     * @return int
     */
    final public function batchUpdate(array $where, array $values): int
    {
        $data = [];
        foreach ($values as $field => $item) {
            if (!is_array($item)) {
                $data[$field] = $item;
                continue;
            }

            $when = '';
            foreach ($item['filter'] as $k => $v) {
                $when .= sprintf("when '%s' then '%s' ", $k, $v);
            }

            $key = $item['field'] ?? $field;

            $data[$field] = Db::raw("case $key {$when} else '{$item['default']}' end");
        }

        if (empty($data)) return 0;

        return $this->buildWhere($where)->update($data);
    }

    /**
     * 根据条件批量删除数据
     *
     * @param array $where 删除的条件
     * @return int
     */
    final public function delete(array $where): int
    {
        return $this->buildWhere($where)->delete();
    }

    /**
     * 打印查询 SQL
     *
     * @param array $where 查询条件
     * @return string
     */
    final public function toSql(array $where): string
    {
        return $this->buildWhere($where)->toSql();
    }

    /**
     * 原生 SQL 查询
     *
     * @param string $query    查询 SQL
     * @param array  $bindings 绑定数据
     * @param bool   $useReadPdo
     * @return array
     */
    final public function sql(string $query, array $bindings = [], bool $useReadPdo = true): array
    {
        return Db::select($query, $bindings, $useReadPdo);
    }
}
