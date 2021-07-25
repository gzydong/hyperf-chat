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

use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Log\LogLevel;

return [
    'app_name'       => env('APP_NAME', 'skeleton'),
    'app_env'        => env('APP_ENV', 'dev'),
    'scan_cacheable' => env('SCAN_CACHEABLE', false),

    // 域名相关配置
    'domain'         => [
        'web_url' => env('WEB_URL', ''),// Web 端首页地址
        'img_url' => env('IMG_URL', ''),// 设置文件图片访问的域名
    ],

    // 管理员邮箱
    'admin_email'    => env('ADMIN_EMAIL', ''),

    'juhe_api' => [
        'ip' => [
            'api' => 'http://apis.juhe.cn/ip/ipNew',
            'key' => env('JUHE_IP_KEY', '')
        ],
    ],

    StdoutLoggerInterface::class => [
        'log_level' => [
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            //LogLevel::DEBUG,
            LogLevel::EMERGENCY,
            LogLevel::ERROR,
            LogLevel::INFO,
            LogLevel::NOTICE,
            LogLevel::WARNING,
        ],
    ],
];
