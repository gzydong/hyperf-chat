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

use Hyperf\Server\Server;
use Hyperf\Server\Event;

return [
    'mode'      => SWOOLE_PROCESS,
    'servers'   => [
        [
            'name'      => 'http',
            'type'      => Server::SERVER_HTTP,
            'host'      => '0.0.0.0',
            'port'      => 9503,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => [Hyperf\HttpServer\Server::class, 'onRequest'],
            ],
        ],
        [
            'name'      => 'ws',
            'type'      => Server::SERVER_WEBSOCKET,
            'host'      => '0.0.0.0',
            'port'      => 9504,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                // 自定义握手处理
                Event::ON_HAND_SHAKE => [Hyperf\WebSocketServer\Server::class, 'onHandShake'],
                Event::ON_MESSAGE    => [Hyperf\WebSocketServer\Server::class, 'onMessage'],
                Event::ON_CLOSE      => [Hyperf\WebSocketServer\Server::class, 'onClose'],
            ],
            'settings'  => [
                // 设置心跳检测
                'heartbeat_idle_time'      => 70,
                'heartbeat_check_interval' => 30,
            ]
        ],
    ],
    'settings'  => [
        'enable_coroutine'      => true,
        'worker_num'            => env('SWOOLE_CPU_NUM', swoole_cpu_num() * 2),
        'pid_file'              => BASE_PATH . '/runtime/hyperf.pid',
        'open_tcp_nodelay'      => true,
        'max_coroutine'         => 100000,
        'open_http2_protocol'   => true,
        'max_request'           => 10000,
        'socket_buffer_size'    => 3 * 1024 * 1024,
        'buffer_output_size'    => 3 * 1024 * 1024,
        'package_max_length'    => 10 * 1024 * 1024,

        // 静态资源配置
        'document_root'         => env('UPLOAD_PATH', BASE_PATH . '/public'),
        'enable_static_handler' => true,
    ],
    'callbacks' => [
        // 自定义启动前事件
        Event::ON_BEFORE_START => [App\Bootstrap\ServerStart::class, 'beforeStart'],
        Event::ON_WORKER_START => [Hyperf\Framework\Bootstrap\WorkerStartCallback::class, 'onWorkerStart'],
        Event::ON_PIPE_MESSAGE => [Hyperf\Framework\Bootstrap\PipeMessageCallback::class, 'onPipeMessage'],
        Event::ON_WORKER_EXIT  => [Hyperf\Framework\Bootstrap\WorkerExitCallback::class, 'onWorkerExit'],
    ],
];
