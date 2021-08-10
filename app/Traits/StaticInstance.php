<?php
declare(strict_types=1);

namespace App\Traits;

/**
 * Trait StaticInstance
 *
 * @package App\Traits
 */
trait StaticInstance
{
    private static $instance;

    private function __construct()
    {
    }

    /**
     * 获取单例
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    private function __clone()
    {
    }
}
