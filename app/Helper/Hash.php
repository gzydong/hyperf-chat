<?php

namespace App\Helper;

/**
 * Hash 密码加密辅助类
 *
 * @package App\Helper
 */
class Hash
{
    /**
     * Hash the given value.
     *
     * @param  string $value
     * @return string
     */
    public static function make(string $value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param  string $value
     * @param  string $hashedValue
     * @return bool
     */
    public static function check(string $value, string $hashedValue)
    {
        return password_verify($value, $hashedValue);
    }
}
