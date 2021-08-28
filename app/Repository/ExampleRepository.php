<?php
declare(strict_types=1);

namespace App\Repository;

use App\Helper\HashHelper;
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
        // $this->increment(['id' => 1017], 'is_robot', 4, [
        //     'updated_at' => date('Y-m-d H:i:s')
        // ]);

        // $this->decrement(['id:gt' => 1017], 'is_robot', 1, [
        //     'updated_at' => date('Y-m-d H:i:s')
        // ]);
    }

    // 聚合查询相关案例 count, max, min, avg, sum
    public function case2()
    {
        // $this->count([
        //     'id:gt' => 3000
        // ]);

        // $this->max([
        //     'id:gt' => 3000
        // ], 'id');

        // $this->min([
        //     'id:gt' => 3000
        // ], 'id');

        // $this->avg([
        //     'id:gt' => 3000
        // ], 'id');

        // $this->sum([
        //     'id:gt' => 3000
        // ], 'id');
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
        // ], ['id', 'mobile'],true);

        // 分页获取数据
        // $this->paginate([
        //     'id:gt'  => 20540000,
        //     'gender' => 2
        // ], ['*'], 1, 15);

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
    }

    // where 条件查询案例
    public function where_case()
    {
        $where = [
            // 等值查询
            'mobile'    => "18798271234",
            'mobile:eq' => "18798271234",

            // model 自带操作符查询
            ['id', '=', 12],
            ['id', '>', 12],
            ['id', '>=', 12],// ...

            // in 或者  not in 查询
            'id:in'     => [1, 2, 3],
            'id'        => [1, 2, 3],
            'id:not in' => [5, 6, 7],
            'id:gt'     => 10,
            'id:lt'     => 100,

            'or' => [
                'field' => '',
                ['field', '>', ''],
            ],
            [
                'field' => '',
                ['field', '>', ''],
            ]
        ];
    }
}
