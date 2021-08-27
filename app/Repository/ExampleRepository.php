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

    // get 查询案例
    public function get_case()
    {
        $model = $this->getNewModel();

        $result = $this->first([
            'id' => 2054,
            'or' => [
                'gender' => [1, 2, 3],
                [
                    'id:lt'  => 2011,
                    'mobile' => 2066,
                ],
                [
                    'id:gt'  => 1344,
                    'mobile' => 1233,
                    'or'     => [
                        'nickname' => "1111",
                        'email'    => '22222'
                    ]
                ],
            ]
        ]);
    }
}
