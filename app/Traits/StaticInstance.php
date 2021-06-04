<?php

namespace App\Traits;

/**
 * Trait StaticInstance
 *
 * @package App\Traits
 */
trait StaticInstance
{
    private static $instance;

    /**
     * 获取单例
     *
     * @return static
     */
    static public function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
