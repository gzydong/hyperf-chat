<?php
declare(strict_types=1);

namespace HyperfTest\Cases;


use App\Repository\ExampleRepository;
use HyperfTest\HttpTestCase;

class RepositoryTest extends HttpTestCase
{
    public function testRepositoryExample()
    {
        $repository = di()->get(ExampleRepository::class);

        $sql1 = $repository->toSql([
            'id' => [1, 2, 3, 4]
        ]);


        $this->assertEquals($sql1,"select * from `lar_users` where `id` in (?, ?, ?, ?)");
    }
}
