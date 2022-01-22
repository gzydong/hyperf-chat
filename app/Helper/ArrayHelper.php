<?php
declare(strict_types=1);

namespace App\Helper;

class ArrayHelper
{
    /**
     * 判断是否为关联数组
     *
     * @param array $items
     * @return bool
     */
    public static function isAssociativeArray(array $items): bool
    {
        $i = 0;
        foreach (array_keys($items) as $value) {
            if (!is_int($value) || $value !== $i) return true;

            $i++;
        }

        return false;
    }

    /**
     * 二维数组排序
     *
     * @param array  $array 数组
     * @param string $field 排序字段
     * @param int    $sort  排序方式
     * @return array
     */
    public static function sort(array $array, string $field, int $sort = SORT_DESC): array
    {
        array_multisort(array_column($array, $field), $sort, $array);
        return $array;
    }
}
