<?php
declare(strict_types=1);

namespace App\Helper;

/**
 * Json 字符串助手
 *
 * @package App\Helpers
 */
class JsonHelper
{
    /**
     * Json 字符串解析
     *
     * @param $value
     * @return mixed
     */
    public static function decode($value)
    {
        return json_decode($value, true);
    }

    /**
     * Json 加密
     *
     * @param $value
     * @return false|string
     */
    public static function encode($value)
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
