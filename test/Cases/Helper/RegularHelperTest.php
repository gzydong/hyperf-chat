<?php
declare(strict_types=1);

namespace HyperfTest\Cases\Helper;

use App\Helper\RegularHelper;
use HyperfTest\HttpTestCase;

class RegularHelperTest extends HttpTestCase
{
    public function testPhoneTest()
    {
        $this->assertTrue(!RegularHelper::verify('phone', ''), '手机号验证失败1!');
        $this->assertTrue(!RegularHelper::verify('phone', ' '), '手机号验证失败2!');
        $this->assertTrue(!RegularHelper::verify('phone', 'test'), '手机号验证失败3!');
        $this->assertTrue(!RegularHelper::verify('phone', '18720431234 '), '手机号验证失败4!');
        $this->assertTrue(!RegularHelper::verify('phone', ' 18720431234'), '手机号验证失败5!');
        $this->assertTrue(!RegularHelper::verify('phone', ' 18720431234 '), '手机号验证失败6!');
        $this->assertTrue(!RegularHelper::verify('phone', '-18720431234 '), '手机号验证失败7!');
        $this->assertTrue(!RegularHelper::verify('phone', '28720431234 '), '手机号验证失败8!');
        $this->assertTrue(!RegularHelper::verify('phone', '18q20431234'), '手机号验证失败9!');
        $this->assertTrue(RegularHelper::verify('phone', '18720431234'), '手机号验证失败10!');
    }

    public function testIdsTest()
    {
        $this->assertTrue(!RegularHelper::verify('ids', ''), 'ids 格式验证失败1!');
        $this->assertTrue(!RegularHelper::verify('ids', ' '), 'ids 格式验证失败2!');
        $this->assertTrue(!RegularHelper::verify('ids', ' 1234'), 'ids 格式验证失败3!');
        $this->assertTrue(!RegularHelper::verify('ids', '1234 '), 'ids 格式验证失败4!');
        $this->assertTrue(!RegularHelper::verify('ids', ' 1234 '), 'ids 格式验证失败5!');
        $this->assertTrue(!RegularHelper::verify('ids', 'test'), 'ids 格式验证失败6!');
        $this->assertTrue(!RegularHelper::verify('ids', 'test,tes'), 'ids 格式验证失败7!');
        $this->assertTrue(!RegularHelper::verify('ids', '123,tes'), 'ids 格式验证失败8!');
        $this->assertTrue(!RegularHelper::verify('ids', '123,1213,tes'), 'ids 格式验证失败9!');
        $this->assertTrue(!RegularHelper::verify('ids', '-123,1213'), 'ids 格式验证失败10!');
        $this->assertTrue(!RegularHelper::verify('ids', '1w23,1213'), 'ids 格式验证失败11!');
        $this->assertTrue(!RegularHelper::verify('ids', '123,1213,'), 'ids 格式验证失败12!');
        $this->assertTrue(!RegularHelper::verify('ids', '123,1213,,'), 'ids 格式验证失败13!');
        $this->assertTrue(!RegularHelper::verify('ids', '123,1213,,234'), 'ids 格式验证失败14!');
        $this->assertTrue(RegularHelper::verify('ids', '123,1213'), 'ids 格式验证失败15!');
    }
}
