<?php

declare(strict_types=1);

/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

use Hyperf\HttpServer\Router\Router;

Router::get('/favicon.ico', function () {
    return '';
});

// 加载 Http 路由
Router::addServer('http', function () {
    require __DIR__ . '/routes/http.php';
});

// 加载 Websocket 路由
Router::addServer('ws', function () {
    require __DIR__ . '/routes/websocket.php';
});
