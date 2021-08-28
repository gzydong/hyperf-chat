<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\User;

class ExampleRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function insert()
    {

    }

    public function case1()
    {
        $this->increment(['id' => 1017], 'is_robot', 4, [
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $this->decrement(['id:gt' => 1017], 'is_robot', 1, [
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function case2()
    {
        $res = $this->pluck(['id:gt' => 1017, 'id:lt' => 1040], 'id');


        var_dump($this->doesntExist([
            'id' => 2054
        ]));
    }

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


    // 聚合查询相关案例 count, max, min, avg, sum
    public function aggregation_case()
    {
        var_dump('count : ' . $this->count([
                'id:gt' => 3000
            ]));

        var_dump('max : ' . $this->max([
                'id:gt' => 3000
            ], 'id'));

        var_dump('min : ' . $this->min([
                'id:gt' => 3000
            ], 'id'));

        var_dump('avg : ' . $this->avg([
                'id:gt' => 3000
            ], 'id'));

        var_dump('sum : ' . $this->sum([
                'id:gt' => 3000
            ], 'id'));
    }

    // get 查询案例
    public function get_case()
    {
        // $result = $this->first([
        //     // 'id' => 2054,
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
}
