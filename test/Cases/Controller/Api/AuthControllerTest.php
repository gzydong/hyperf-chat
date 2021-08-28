<?php
declare(strict_types=1);

namespace HyperfTest\Cases\Controller\Api;

use HyperfTest\HttpTestCase;

/**
 * 授权控制器单元测试
 *
 * Class AuthControllerTest
 * @package HyperfTest\Cases\Controller\Api
 */
class AuthControllerTest extends HttpTestCase
{
    public function testLogin()
    {
        $response = $this->post('/api/v1/auth/login', [
            'mobile'   => '231231',
            'password' => 'asdfasf',
            'platform' => 'sdfas',
        ]);

        $this->assertArrayHasKey('code', $response);
    }
}
