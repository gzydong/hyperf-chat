<?php


use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Server\ServerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Websocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;


/**
 * 容器实例
 */
if (!function_exists('container')) {
    function container()
    {
        return ApplicationContext::getContainer();
    }
}


/**
 * Redis 客户端实例
 */
if (!function_exists('redis')) {
    function redis()
    {
        return container()->get(Redis::class);
    }
}

/**
 * server 实例 基于 swoole server
 */
if (!function_exists('server')) {
    function server()
    {
        return container()->get(ServerFactory::class)->getServer()->getServer();
    }
}

/**
 * websocket frame 实例
 */
if (!function_exists('frame')) {
    function frame()
    {
        return container()->get(Frame::class);
    }
}

/**
 * websocket 实例
 */
if (!function_exists('websocket')) {
    function websocket()
    {
        return container()->get(WebSocketServer::class);
    }
}

/**
 * 缓存实例 简单的缓存
 */
if (!function_exists('cache')) {
    function cache()
    {
        return container()->get(Psr\SimpleCache\CacheInterface::class);
    }
}

/**
 * 控制台日志
 */
if (!function_exists('stdLog')) {
    function stdLog()
    {
        return container()->get(StdoutLoggerInterface::class);
    }
}

/**
 * 文件日志
 */
if (!function_exists('logger')) {
    function logger(string $name = 'APP')
    {
        return container()->get(LoggerFactory::class)->get($name);
    }
}

/**
 * http 请求实例
 */
if (!function_exists('request')) {
    function request()
    {
        return container()->get(ServerRequestInterface::class);
    }
}

/**
 * 请求响应
 */
if (!function_exists('response')) {
    function response()
    {
        return container()->get(ResponseInterface::class);
    }
}

/**
 * 获取加密后的密码字符
 *
 * @param string $password
 * @return bool|false|null|string
 */
function create_password(string $password){
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * 从HTML文本中提取所有图片
 * @param $content
 * @return array
 */
function get_html_images($content)
{
    $pattern = "/<img.*?src=[\'|\"](.*?)[\'|\"].*?[\/]?>/";
    preg_match_all($pattern, htmlspecialchars_decode($content), $match);
    $data = [];
    if (!empty($match[1])) {
        foreach ($match[1] as $img) {
            if (!empty($img)) $data[] = $img;
        }
        return $data;
    }

    return $data;
}

/**
 * 获取两个日期相差多少天
 *
 * @param $day1
 * @param $day2
 * @return float|int
 */
function diff_date($day1, $day2)
{
    $second1 = strtotime($day1);
    $second2 = strtotime($day2);

    if ($second1 < $second2) {
        [$second1, $second2] = [$second2, $second1];
    }

    return ceil(($second1 - $second2) / 86400);
}
