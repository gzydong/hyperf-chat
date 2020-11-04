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
use App\Middleware\CorsMiddleware;


Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');

Router::post('/upload', 'App\Controller\IndexController@upload',['middleware' => [CorsMiddleware::class]]);






Router::get('/favicon.ico', function () {
    return '';
});

Router::addServer('ws', function () {
    Router::get('/', 'App\Controller\WebSocketController');
});
