<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2020/11/4
 * Time: 11:43
 */

namespace App\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

/**
 * @Constants
 */
class ResponseCode extends AbstractConstants
{
    const SUCCESS = 200;   // 接口处理成功
    const FAIL = 305;   // 接口处理失败

    /**
     * @Message("Server Error！")
     */
    const SERVER_ERROR = 500;

    /**
     * @Message("请求数据验证失败！")
     */
    const VALIDATION_ERROR = 301;
}
