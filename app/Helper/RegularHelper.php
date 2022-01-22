<?php
declare(strict_types=1);

namespace App\Helper;

/**
 * 正则验证助手
 *
 * @package App\Helper
 */
class RegularHelper
{
    // 手机号正则
    const REG_PHONE = '/^1[3456789][0-9]{9}$/';

    // 逗号拼接的正整数
    const REG_IDS = '/^\d+(\,\d+)*$/';

    // 正则别名
    const REG_MAP = [
        'phone' => self::REG_PHONE,
        'ids'   => self::REG_IDS,
    ];

    /**
     * 通过别名获取正则表达式
     *
     * @param string $regular 别名
     * @return string
     */
    public static function getAliasRegular(string $regular): string
    {
        return self::REG_MAP[$regular];
    }

    /**
     * 正则验证
     *
     * @param string     $regular 正则名称
     * @param int|string $value   验证数据
     * @return bool
     */
    public static function verify(string $regular, $value): bool
    {
        return (bool)preg_match(self::getAliasRegular($regular), $value);
    }
}
