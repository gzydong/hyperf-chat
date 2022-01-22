<?php
declare(strict_types=1);

namespace App\Repository;

use App\Helper\HashHelper;
use App\Model\Talk\TalkRecords;
use App\Model\User;
use Hyperf\Utils\Str;

/**
 * Repository 使用案例
 *
 * @package App\Repository
 */
class ExampleRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    // 自增自减案例 increment decrement
    public function case1()
    {
        // 数据自增
        // $this->increment(['id' => 1017], 'is_robot', 4, [
        //     'updated_at' => date('Y-m-d H:i:s')
        // ]);

        // 数据自减
        // $this->decrement(['id:gt' => 1017], 'is_robot', 1, [
        //     'updated_at' => date('Y-m-d H:i:s')
        // ]);
    }

    // 聚合查询相关案例 count, max, min, avg, sum
    public function case2()
    {
        // 统计总数
        // $this->count(['id:gt' => 3000]);

        // 最大值
        // $this->max(['id:gt' => 3000], 'id');

        // 最小值
        // $this->min(['id:gt' => 3000], 'id');

        // 平均值
        // $this->avg(['id:gt' => 3000], 'id');

        // 求和
        // $this->sum(['id:gt' => 3000], 'id');
    }

    // model value pluck exists doesntExist
    public function case3()
    {
        // $this->value(['id' => 20540000], 'id');

        // $this->pluck(['id:gt' => 1017, 'id:lt' => 1040], 'mobile');

        // $this->exists(['id' => 2054]);

        // $this->doesntExist(['id' => 2054]);
    }

    // model 原生方法
    public function case4()
    {
        // 创建一条数据
        // $this->create([
        //     'mobile'     => '135' . mt_rand(1000, 9999) . mt_rand(1000, 9999),
        //     'nickname'   => Str::random(10),
        //     'password'   => HashHelper::make('aa123456'),
        //     'created_at' => date('Y-m-d H:i:s'),
        //     'updated_at' => date('Y-m-d H:i:s'),
        // ]);

        // 批量创建数据
        // $this->insert([
        //     [
        //         'mobile'     => '135' . mt_rand(1000, 9999) . mt_rand(1000, 9999),
        //         'nickname'   => Str::random(10),
        //         'password'   => HashHelper::make('aa123456'),
        //         'created_at' => date('Y-m-d H:i:s'),
        //         'updated_at' => date('Y-m-d H:i:s'),
        //     ],
        //     [
        //         'mobile'     => '135' . mt_rand(1000, 9999) . mt_rand(1000, 9999),
        //         'nickname'   => Str::random(10),
        //         'password'   => HashHelper::make('aa123456'),
        //         'created_at' => date('Y-m-d H:i:s'),
        //         'updated_at' => date('Y-m-d H:i:s'),
        //     ],
        // ]);

        // 创建一条数据并返回主键ID
        // $user_id = $this->insertGetId([
        //     'mobile'     => '135' . mt_rand(1000, 9999) . mt_rand(1000, 9999),
        //     'nickname'   => Str::random(10),
        //     'password'   => HashHelper::make('aa123456'),
        //     'created_at' => date('Y-m-d H:i:s'),
        //     'updated_at' => date('Y-m-d H:i:s'),
        // ]);

        // 查询一条数据不存在即新增一条数据
        // $user = $this->firstOrCreate([
        //     'mobile' => 18698272054,
        // ], [
        //     'mobile'     => 18698272054,
        //     'nickname'   => Str::random(10),
        //     'password'   => HashHelper::make('aa123456'),
        //     'created_at' => date('Y-m-d H:i:s'),
        //     'updated_at' => date('Y-m-d H:i:s'),
        // ]);

        // 更新一条数据不存在就创建
        // $this->updateOrCreate([
        //     'mobile' => 18698272054,
        // ], [
        //     'mobile'     => 18698272054,
        //     'nickname'   => Str::random(10),
        //     'password'   => HashHelper::make('aa123456'),
        //     'created_at' => date('Y-m-d H:i:s'),
        //     'updated_at' => date('Y-m-d H:i:s'),
        // ]);

        // 根据主键ID查询数据
        // $this->find(2054, ['id', 'mobile']);

        // 主键查询没有就抛出错误
        // $this->findOrFail(20540000, ['id', 'mobile']);

        // 根据条件更新数据
        // $this->update([
        //     'id' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        // ], [
        //     'gender'     => 2,
        //     'updated_at' => date('Y-m-d H:i:s'),
        // ]);

        // 批量更新数据
        // $this->batchUpdate([
        //     'id:gt' => 2054
        // ], [
        //     'email'  => '',       // 不使用条件判断，默认更新
        //     'gender' => [
        //         'field'   => 'id',//判断的字段，可选（不设置默认使用当前字段）
        //         'default' => 0,   // 默认字段值
        //         'filter'  => [    // 数据判断
        //             '2054' => 1,
        //             '2055' => 2,
        //         ]
        //     ],
        // ]);

        // 批量删除数据
        // $this->delete([
        //     'id' => 4241
        // ]);
    }

    public function case5()
    {
        // 根据条件获取满足条件的第一条数据
        // $result = $this->first([
        //     'id' => 2054,
        // ], ['*'], true);

        // 根据条件获取所有满足条件的数据
        // $this->get([
        //     'id'     => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        //     'gender' => 2
        // ], ['id', 'mobile'],[0,10]);

        // 分页获取数据
        // $data = $this->paginate([
        //     'id:gt' => 2054,
        // ], ['id', 'mobile'], 1, 5);
        //
        // var_dump($data);

        // 打印查询 sql 语句
        // $this->toSql([
        //     'id' => 2054,
        //     'or' => [
        //         'gender' => [1, 2, 3],
        //         [
        //             'id:lt'  => 2011,
        //             'mobile' => 2066,
        //         ],
        //         [
        //             'id:gt'  => 1344,
        //             'mobile' => 1233,
        //             'or'     => [
        //                 'nickname' => "1111",
        //                 'email'    => '22222'
        //             ]
        //         ],
        //     ]
        // ]);

        // 原生 SQL 查询
        // $this->sql('SELECT * FROM `lar_users` WHERE id = ?', [2054]);
    }

    public function other()
    {
        // 获取 Model 实例
        $model = $this->buildWhere();

    }

    // where 条件查询案例
    public function where_case()
    {
        $where = [
            // 等值查询
            'mobile'          => "18798271234",
            'mobile:eq'       => "18798271234",

            // model 自带操作符查询
            ['id', '=', 12],
            ['id', '>', 12],
            ['id', '>=', 12],// ...

            // in 或者  not in 查询
            'id:in'           => [1, 2, 3],
            'id:not in'       => [5, 6, 7],
            'id:gt'           => 10,
            'id:lt'           => 100,
            'mobile:like'     => "138%",
            'mobile:not like' => "1381%",

            // or 查询（可嵌套使用）
            'or'              => [
                'field' => '',
                ['field', '>', ''],
                'or'    => [
                    ['field', '>', ''],
                ],
                ['field', '>', ''],
            ],

            // 排序
            'order by'        => [
                'created_at'                => 'desc',
                'updated_at'                => 'asc',
                '`updated_at - created_at`' => 'desc'
            ],

            // 分组
            'group by'        => [
                'gender', 'is_robot',
            ],
            'having by'       => [
                ['account_id', '>', 100],
            ],

            // 关联查询
            'join table'      => [
                // orm 自带条件
                // [$table, $first, $operator = null, $second = null, $type = 'inner']

                // 数组方式
                [
                    'users_emoticon', 'users_emoticon.user_id', '=', 'users.id', 'left'
                ],

                // 闭包方法
                ['users_emoticon', function ($join) {
                    $join->on('users.id', '=', 'users_emoticon.user_id');
                }, null, null, 'left']
            ]
        ];

        echo $this->buildWhere($where)->toSql();
    }

    public function where_case2()
    {
        $this->get([
            'id:gt'      => 3000,
            'id:lt'      => 4000,
            'id:between' => [3000, 4000],
            'or'         => [
                ['mobile', 'like', '138%'],
                ['mobile', 'like', '139%'],
                [
                    ['id', '=', 12],
                    ['id', '=', 13],
                    'or' => [
                        ['id', '=', 12],
                        ['id', '=', 13],
                    ]
                ]
            ],
            'is_robot'   => 1,
        ]);
        // select * from `lar_users` where `id` > '3000' and `id` < '4000' and `id` between '3000' and '4000' and (`mobile` like '138%' or `mobile` like '139%' or (`id` = '12' and `id` = '13')) and `is_robot` = '1'
    }
}
