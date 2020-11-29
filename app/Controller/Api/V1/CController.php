<?php

namespace App\Controller\Api\V1;

use Hyperf\Di\Annotation\Inject;
use App\Controller\AbstractController;
use App\Support\Response;
use Phper666\JWTAuth\JWT;

/**
 * 基类控制器
 *
 * Class CController
 * @package App\Controller\Api\V1
 */
class CController extends AbstractController
{
    /**
     * @Inject
     * @var Response
     */
    protected $response;

    /**
     * 获取当前登录用户ID
     *
     * @return int
     */
    public function uid(){
        $token = request()->getQueryParams()['token']??null;
        $data = container()->get(JWT::class)->getParserData($token);
        return $data['user_id'];
    }
}
