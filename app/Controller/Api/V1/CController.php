<?php

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Supports\Http\Response;
use Hyperf\Di\Annotation\Inject;

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
}
