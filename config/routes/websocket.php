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


// 添加 ws 服务对应的路由
Router::get('/socket.io', 'App\Controller\WebSocketController', [
    'middleware' => [\App\Middleware\WebSocketAuthMiddleware::class]
]);