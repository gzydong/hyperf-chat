<?php
declare(strict_types=1);

namespace HyperfTest\Cases\Helper;


use App\Helper\ArrayHelper;
use HyperfTest\HttpTestCase;

class ArrayHelperTest extends HttpTestCase
{
    public function testIsRelationArray()
    {
        $this->assertTrue(!ArrayHelper::isAssociativeArray([1, 2, 3, 4, 5]), '判断是否是关联数组失败1！');
        $this->assertTrue(ArrayHelper::isAssociativeArray([1, 6 => 2, 3, 4, 5]), '判断是否是关联数组失败2！');
        $this->assertTrue(ArrayHelper::isAssociativeArray(['k1' => 'test', 'k2' => 'test2']), '判断是否是关联数组失败3！');
    }
}
