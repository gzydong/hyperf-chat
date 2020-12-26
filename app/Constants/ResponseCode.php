<?php
/**
 *
 * This is my open source code, please do not use it for commercial applications.
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author Yuandong<837215079@qq.com>
 * @link   https://github.com/gzydong/hyperf-chat
 */
namespace App\Constants;

/**
 * HTTP 响应状态码枚举
 *
 * Class ResponseCode
 * @package App\Constants
 */
class ResponseCode
{
    const SUCCESS = 200;   // 接口处理成功
    const FAIL = 305;   // 接口处理失败

    /**
     * Server Error！
     */
    const SERVER_ERROR = 500;

    /**
     * 请求数据验证失败！
     */
    const VALIDATION_ERROR = 301;
}
