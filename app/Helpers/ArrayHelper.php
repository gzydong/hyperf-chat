<?php
declare(strict_types=1);

namespace App\Helpers;

class ArrayHelper
{
    /**
     * 判断是否为关联数组
     *
     * @param array $items
     * @return bool
     */
    public static function isRelationArray(array $items): bool
    {
        $i = 0;
        foreach (array_keys($items) as $value) {
            if (!is_int($value) || $value !== $i) return true;

            $i++;
        }

        return false;
    }
}
