<?php
declare(strict_types=1);

namespace App\Helper;

/**
 * Hash 密码加密辅助类
 *
 * @package App\Helper
 */
class HashHelper
{
    /**
     * Hash the given value.
     *
     * @param string $value
     * @return string
     */
    public static function make(string $value): string
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param string $value
     * @param string $hashedValue
     * @return bool
     */
    public static function check(string $value, string $hashedValue): bool
    {
        return password_verify($value, $hashedValue);
    }
}
