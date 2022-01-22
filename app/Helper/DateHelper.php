<?php
declare(strict_types=1);

namespace App\Helper;

/**
 * 时间处理助手
 *
 * @package App\Helpers
 */
class DateHelper
{
    /**
     * 获取两个日期相差多少天
     *
     * @param string $day1 日期1
     * @param string $day2 日期2
     * @return int
     */
    public static function diff(string $day1, string $day2): int
    {
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);

        if ($second1 < $second2) {
            [$second1, $second2] = [$second2, $second1];
        }

        return intval(ceil(($second1 - $second2) / 86400));
    }
}
