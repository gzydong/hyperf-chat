<?php
declare(strict_types=1);

namespace App\Traits;

use App\Helper\ArrayHelper;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Arr;
use Exception;

trait RepositoryTrait
{
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
     * Model 不需要查询条件的方法
     *
     * @var string[]
     */
    private $origin = [
        'create', 'insert', 'insertGetId',
        'firstOrCreate', 'firstOrNew',
        'updateOrCreate', 'updateOrInsert',
        'findOrFail', 'findOrNew', 'find',
        'getConnection',
    ];

    /**
     * 父类需要查询条件的相关方法
     *
     * @var string[]
     */
    private $parent = [
        'count', 'max', 'min', 'avg', 'sum',
        'increment', 'decrement',
        'value', 'pluck',
        'exists', 'doesntExist'
    ];

    /**
     * @var Model
     */
    private $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * 获取 Model 实例类
     *
     * @return Model
     */
    final public function getModel(): Model
    {
        return $this->model;
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
        if (in_array($method, $this->origin)) {
            return (new $this->model)->{$method}(...$arguments);
        }

        // 调用 model 原生方法
        if (in_array($method, $this->parent)) {
            $where = Arr::pull($arguments, '0', []);
            return $this->buildWhere($where)->{$method}(...$arguments);
        }

        throw new \Exception("Uncaught Error: Call to undefined method {$method}");
    }

    /**
     * 获取新的 Model 查询构造器
     *
     * @return Builder
     */
    protected function getBuilder(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * 构建 Where 查询 Model
     *
     * @param array $where
     * @return Builder
     */
    final public function buildWhere(array $where = []): Builder
    {
        $model = $this->getBuilder();

        // Join 关联处理
        if ($joins = Arr::pull($where, 'join table')) {
            $this->addJoinTable($model, (array)$joins);
        }

        // 处理排序数据
        if ($order = Arr::pull($where, 'order by')) {
            $this->addOrderBy($model, (array)$order);
        }

        // 处理分组数据
        if ($group = Arr::pull($where, 'group by')) {
            $this->addGroupBy($model, (array)$group);
        }

        // 处理分组数据
        if ($having = Arr::pull($where, 'having by')) {
            $this->addHaving($model, (array)$having);
        }

        // 判断是否存在查询条件
        if (!empty($where)) {
            if (count($where) === 1 && isset($where['or'])) {
                $where = $where['or'];
            }

            $this->bindWhere($model, $where);
        }

        return $model;
    }

    /**
     * @param Builder $model
     * @param array   $where
     * @param bool    $or
     * @throws Exception
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

                $this->addNewWhere($model, $item, $or);
                continue;
            }

            // 字段查询
            $this->setFieldWhere($model, $field, $item, $or);
        }
    }

    /**
     * 添加 where 条件分组
     *
     * @param Builder $model
     * @param array   $where
     * @param bool    $or
     * @param string  $type
     * @throws Exception
     */
    private function addNewWhere(Builder $model, array $where, $or = false, string $type = 'and')
    {
        $method = $or ? 'orWhere' : 'where';

        $model->{$method}(function ($query) use ($where, $or, $type) {
            $this->bindWhere($query, $where, $type === 'or');
        });
    }

    /**
     * Join 关联查询
     *
     * @param \Hyperf\Database\Model\Builder $model
     * @param array                          $joins
     */
    private function addJoinTable(Builder $model, array $joins)
    {
        foreach ($joins as $join) {
            $model->join(...$join);
        }
    }

    /**
     * 添加排序信息
     *
     * @param Builder $model
     * @param array   $orders
     */
    private function addOrderBy(Builder $model, array $orders)
    {
        foreach ($orders as $field => $sort) {
            if ($this->isBackQuote($field)) {
                $model->orderByRaw($this->trimBackQuote($field) . ' ' . $sort);
            } else {
                $model->orderBy($field, $sort);
            }
        }
    }

    /**
     * 添加分组信息
     *
     * @param Builder $model
     * @param array   $groups
     */
    private function addGroupBy(Builder $model, array $groups)
    {
        $model->groupBy(...$groups);
    }

    /**
     * @param Builder $model
     * @param array   $items
     */
    private function addHaving(Builder $model, array $items = [])
    {
        foreach ($items as $having) {
            $model->{count($having) == 2 ? 'havingRaw' : 'having'}(...$having);
        }
    }

    /**
     * 设置条件查询
     *
     * @param Builder          $model
     * @param string           $field
     * @param string|int|array $value
     * @param bool             $or
     * @throws \Exception
     */
    private function setFieldWhere(Builder $model, string $field, $value, $or = false)
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
    private function handleField(array &$fields)
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

        return Db::raw($this->trimBackQuote($field));
    }

    /**
     * 判断字符串是否被反引号包含
     *
     * @param string $string
     * @return bool
     */
    private function isBackQuote(string $string): bool
    {
        return (bool)preg_match("/^`.*?`$/", $string);
    }

    /**
     * 去除字符串两端的反引号
     *
     * @param string $field
     * @return string
     */
    private function trimBackQuote(string $field): string
    {
        return substr($field, 1, strlen($field) - 2);
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
        if (ArrayHelper::isAssociativeArray($items)) {
            return false;
        }

        foreach ($items as $item) {
            if (is_array($item)) return false;
        }

        return in_array($items[1], $this->operators);
    }
}
