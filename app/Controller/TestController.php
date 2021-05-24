<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

/**
 * 测试相关控制器
 * @Controller()
 * @package App\Controller
 */
class TestController extends AbstractController
{
    /**
     * @RequestMapping(path="indexss", methods="get")
     */
    public function index()
    {
        return $this->response->json([
            'code' => 200
        ]);
    }
}
