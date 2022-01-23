<?php
declare(strict_types=1);

namespace App\Template;

abstract class BaseTemplate
{
    /**
     * 获取模板信息
     *
     * @param string $engine   模板引擎
     * @param string $template 模板名称
     * @param array  $params   模板参数
     * @return string
     */
    protected function view(string $engine, string $template, $params = []): string
    {
        return di()->get($engine)->render($template, $params, config('view.config', []));
    }
}
