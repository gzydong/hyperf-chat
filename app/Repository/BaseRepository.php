<?php
declare(strict_types=1);

namespace App\Repository;

use App\Traits\PagingTrait;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Arr;

/**
 * Class BaseRepository
 *
 * @method boolean insert(array $values) 新增数据
 * @method Model create(array $values) 新增数据
 * @method int|mixed insertGetId(array $values, $sequence = null) 新增数据获取新增ID
 * @method Model firstOrCreate(array $attributes, array $value = []) 查询数据没有就创建
 * @method Model firstOrNew(array $attributes, array $value = []) 查询数据没有就实例化
 * @method Model updateOrCreate(array $attributes, array $value = []) 查询修改没有就创建
 * @method Model updateOrInsert(array $attributes, array $values = []) 查询修改没有就实例化
 * @method Model find($id, $columns = ['*']) 主键查询
 * @method Model findOrFail($id, $columns = ['*']) 主键查询没有就抛出错误
 * @method Model findOrNew($id, $columns = ['*']) 主键查询没有就实例化
 *
 * @package App\Repository
 */
abstract class BaseRepository
{
    use PagingTrait;

    /**
     * @var Model
     */
    private $model;

    /**
     * 自带查询的表达式
     *
     * @var string[]
     */
    private $operators = [
        '=',
        '<',
        '>',
        '<=',
        '>=',
        '<>',
        '!=',
        '<=>',
        'like',
        'like binary',
        'not like',
        'ilike',
        '&',
        '|',
        '^',
        '<<',
        '>>',
        'rlike',
        'regexp',
        'not regexp',
        '~',
        '~*',
        '!~',
        '!~*',
        'similar to',
        'not similar to',
        'not ilike',
        '~~*',
        '!~~*'
    ];

    /**
     * 扩展查询的表达式
     *
     * @var string[]
     */
    private $expression = [
        'eq'          => '=',
        'neq'         => '!=',
        'ne'          => '!=',
        'gt'          => '>',
        'egt'         => '>=',
        'gte'         => '>=',
        'ge'          => '>=',
        'lt'          => '<',
        'le'          => '<=',
        'lte'         => '<=',
        'elt'         => '<=',
        'in'          => 'In',
        'not in'      => 'NotIn',
        'between'     => 'Between',
        'not between' => 'NotBetween',
        'like'        => 'like',
        'not like'    => 'not like',
        'rlike'       => 'rlike',
        '<>'          => '<>',
        '<=>'         => '<=>',
    ];

    /**
     * @var array 不需要查询条件的方法
     */
    private $originFunction = [
        'create', 'insert', 'insertGetId', 'getConnection', 'firstOrCreate', 'firstOrNew',
        'updateOrCreate', 'findOrFail', 'findOrNew', 'updateOrInsert', 'find'
    ];

    /**
     * BaseRepository constructor.
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * 调用 model 的方法
     *
     * @param string $method 调用model 自己的方法
     * @param array  $arguments
     *
     * @return mixed
     * @throws \Exception
     */
    public function __call(string $method, array $arguments)
    {
        // 直接使用 model, 不需要查询条件的数据
        if (in_array($method, $this->originFunction)) {
            return (new $this->model)->{$method}(...$arguments);
        }

        throw new \Exception("Uncaught Error: Call to undefined method {$method}");
    }

    /**
     * @return Builder
     */
    protected function getNewModel(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * 获取单条数据
     *
     * @param array    $where  查询条件
     * @param string[] $fields 查询字段
     * @return array|Model|null
     */
    public function first(array $where = [], array $fields = ['*'], $isArray = false)
    {
        $this->handleFindField($fields);

        $result = $this->buildWhere($where)->first($fields);

        if ($isArray) return $result ? $result->toArray() : [];

        return $result;
    }

    /**
     * 获取多条数据
     *
     * @param array    $where  查询条件
     * @param string[] $fields 查询字段
     * @return array
     */
    public function get(array $where = [], array $fields = ['*']): array
    {
        $this->handleFindField($fields);

        return $this->buildWhere($where)->get($fields)->toArray();
    }

    /**
     * 根据条件更新数据
     *
     * @param array $where
     * @param array $values
     * @return int
     */
    public function update(array $where, array $values): int
    {
        return $this->buildWhere($where)->update($values);
    }

    /**
     * 获取数据总数
     *
     * @param array $where 查询条件
     *
     * @return int
     */
    public function count(array $where = []): int
    {
        return $this->buildWhere($where)->count();
    }

    /**
     * 分页查询数据
     *
     * @param array $where  查询条件
     * @param array $fields 查询字段
     * @param int   $page   当前页
     * @param int   $size   每页条数
     */
    public function paginate(array $where, $fields = ['*'], $page = 1, $size = 10): ?array
    {
        $this->handleFindField($fields);

        $result = $this->buildWhere($where)->paginate($size, $fields, 'page', $page);

        if (empty($result)) return null;

        return $this->getPagingRows(collect($result->items())->toArray(), $result->total(), $page, $size);
    }

    /**
     * 根据 where 条件打印 sql
     *
     * @param array $where
     * @return string
     */
    public function toSql(array $where): string
    {
        return $this->buildWhere($where)->toSql();
    }

    /**
     * 处理 where 条件
     *
     * @param array $where
     * @return Builder
     */
    public function buildWhere(array $where = []): Builder
    {
        $model = $this->getNewModel();

        if ($order = Arr::pull($where, 'order by')) {
            $this->addOrderBy($model, (array)$order);
        }

        if ($group = Arr::pull($where, 'group by')) {
            $this->addGroupBy($model, (array)$group);
        }

        if (!empty($where)) {
            $this->bindWhere($model, $where);
        }

        return $model;
    }

    /**
     * @param \Hyperf\Database\Model\Builder $model
     * @param array                          $where
     */
    private function bindWhere(Builder $model, array $where, $or = false)
    {
        foreach ($where as $field => $item) {
            if ($field === 'or' || $field === 'and') {
                $this->addNewWhere($model, $item, $or, $field);
                continue;
            }

            if (is_int($field)) {
                if ($this->isModelQueryArray($item)) {
                    $model->{$or ? 'orWhere' : 'where'}(...$item);
                    continue;
                }

                $this->addNewWhere($model, $item, $or, $field);
                continue;
            }

            // 字段查询
            $this->setFieldWhere($model, $field, $item, $or);
        }
    }

    /**
     * 添加 where 条件分组
     *
     * @param \Hyperf\Database\Model\Builder $model
     * @param array                          $where
     * @param bool                           $or
     */
    private function addNewWhere(Builder $model, array $where, $or = false, $field = '')
    {
        $method = $or ? 'orWhere' : 'where';

        $model->{$method}(function ($query) use ($where, $or, $field) {
            $this->bindWhere($query, $where, $field === 'or');
        });
    }

    /**
     * 添加排序信息
     *
     * @param \Hyperf\Database\Model\Builder $model
     * @param array                          $orders
     */
    private function addOrderBy(Builder $model, array $orders)
    {
        foreach ($orders as $field => $sort) {
            if ($this->isBackQuote($field)) {
                $model->orderByRaw(trim($field, '`') . ' ' . $sort);
            } else {
                $model->orderBy($field, $sort);
            }
        }
    }

    /**
     * 添加分组信息
     *
     * @param \Hyperf\Database\Model\Builder $model
     * @param array                          $groups
     */
    private function addGroupBy(Builder $model, array $groups)
    {
        $model->groupBy(...$groups);
    }

    /**
     * 设置条件查询
     *
     * @param \Hyperf\Database\Model\Builder $model
     * @param string                         $field
     * @param string|int|array               $value
     * @return void
     * @throws \Exception
     */
    private function setFieldWhere(Builder $model, string $field, $value, $or = false): void
    {
        [$field, $operator] = $this->formatField($field);

        // 查询数据是数组且未设置表达式，默认是 In 查询
        if ($operator === 'eq' && is_array($value)) {
            $operator = 'in';
        }

        // 验证查询表达式
        if (!isset($this->expression[$operator])) {
            throw new \Exception("无效的 {$operator} 操作符！");
        }

        $method = $or ? 'orWhere' : 'where';

        // 数组查询方式
        if (in_array($this->expression[$operator], ['In', 'NotIn', 'Between', 'NotBetween'], true)) {
            $method = $method . $this->expression[$operator];
            $model->{$method}($field, (array)$value);
            return;
        }

        $model->{$method}($field, $this->expression[$operator], $value);
    }

    /**
     * 解析查询字段信息
     *
     * @param string $field 查询字段
     * @return array
     */
    private function formatField(string $field): array
    {
        $item     = explode(':', $field);
        $field    = $item[0];
        $operator = $item[1] ?? 'eq';

        return [strtolower($field), trim($operator)];
    }

    /**
     * 处理查询字段
     *
     * @param array $fields 查询字段
     */
    private function handleFindField(array &$fields)
    {
        foreach ($fields as $k => $field) {
            $fields[$k] = $this->raw($field);
        }
    }

    /**
     * 去除字段串两端反引号
     *
     * @param string $field
     * @return \Hyperf\Database\Query\Expression|string
     */
    private function raw(string $field)
    {
        // 匹配使用反引号的字段
        if (!$this->isBackQuote($field)) return $field;

        return Db::raw(substr($field, 1, strlen($field) - 2));
    }

    /**
     * 判断字符串是否被反引号包含
     *
     * @param string $string
     * @return false|int
     */
    private function isBackQuote(string $string)
    {
        return preg_match("/^`.*?`$/", $string);
    }

    /**
     * 判断是否为关联数组
     *
     * @param array $items
     * @return bool
     */
    private function isRelationArray(array $items): bool
    {
        $i = 0;
        foreach (array_keys($items) as $value) {
            if (!is_int($value) || $value !== $i) return true;

            $i++;
        }

        return false;
    }

    /**
     * 判断是否是调用 where 最基本的方式是需要传递三个参数
     *
     * @param array $items
     * @return bool
     */
    private function isModelQueryArray(array $items): bool
    {
        if (count($items) != 3) {
            return false;
        }

        // 判断是否是关联数组
        if ($this->isRelationArray($items)) {
            return false;
        }

        foreach ($items as $item) {
            if (is_array($item)) return false;
        }

        return in_array($items[1], $this->operators);
    }
}
