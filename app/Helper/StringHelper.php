<?php
declare(strict_types=1);
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

namespace App\Helper;

/**
 * 字符串助手类
 *
 * Class StringHelper
 * @package App\Helper
 */
class StringHelper
{
    /**
     * 将字符串转换成二进制
     *
     * @param string $str
     * @return string
     */
    public static function str2Bin(string $str): string
    {
        //列出每个字符
        $arr = preg_split('/(?<!^)(?!$)/u', $str);
        //unpack字符
        foreach ($arr as &$v) {
            $temp = unpack('H*', $v);
            $v = base_convert($temp[1], 16, 2);
            unset($temp);
        }

        return join(' ', $arr);
    }

    /**
     * 将二进制转换成字符串
     *
     * @param string $str
     * @return string
     */
    public static function bin2Str(string $str): string
    {
        $arr = explode(' ', $str);
        foreach ($arr as &$v) {
            $v = pack('H' . strlen(base_convert($v, 2, 16)), base_convert($v, 2, 16));
        }

        return join('', $arr);
    }
}
