<?php

namespace App\Helper;

class Hash
{
    /**
     * Hash the given value.
     *
     * @param  string $value
     * @return string
     */
    public function make($value)
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
    public function check($value, $hashedValue)
    {
        return password_verify($value, $hashedValue);
    }
}
