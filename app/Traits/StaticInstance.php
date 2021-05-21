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
        if (!(self::$instance instanceof static)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    private function __clone()
    {

    }
}
