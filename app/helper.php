<?php

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Server\ServerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Websocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;
use Hyperf\Utils\Str;
use Hyperf\Redis\Redis;

/**
 * 容器实例
 */
function container()
{
    return ApplicationContext::getContainer();
}

/**
 * Redis 客户端实例
 */
function redis()
{
    return container()->get(Redis::class);
}

/**
 * server 实例 基于 swoole server
 */
function server()
{
    return container()->get(ServerFactory::class)->getServer()->getServer();
}


/**
 * websocket frame 实例
 */
function frame()
{
    return container()->get(Frame::class);
}

/**
 * websocket 实例
 */
function websocket()
{
    return container()->get(WebSocketServer::class);
}

/**
 * 缓存实例 简单的缓存
 */
function cache()
{
    return container()->get(Psr\SimpleCache\CacheInterface::class);
}

/**
 * 控制台日志
 */
function stdout_log()
{
    return container()->get(StdoutLoggerInterface::class);
}

/**
 * 文件日志
 */
function logger(string $name = 'APP')
{
    return container()->get(LoggerFactory::class)->get($name);
}

/**
 * http 请求实例
 */
function request()
{
    return container()->get(ServerRequestInterface::class);
}

/**
 * 请求响应
 */
function response()
{
    return container()->get(ResponseInterface::class);
}

/**
 * 获取加密后的密码字符
 *
 * @param string $password
 * @return bool|false|null|string
 */
function create_password(string $password)
{
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


/**
 * 获取媒体文件url
 *
 * @param string $path 文件相对路径
 * @return string
 */
function get_media_url(string $path)
{
    return '/' . $path;
}

/**
 * 随机生成图片名
 *
 * @param string $ext 图片后缀名
 * @param int $width 图片宽度
 * @param int $height 图片高度
 * @return string
 */
function create_image_name(string $ext, int $width, int $height)
{
    return uniqid() . Str::random(18) . uniqid() . '_' . $width . 'x' . $height . '.' . $ext;
}

/**
 * 替换文本中的url 为 a标签
 *
 * @param string $str
 * @return null|string|string[]
 */
function replace_url_link(string $str)
{
    $re = '@((https|http)?://([-\w\.]+)+(:\d+)?(/([\w/_\-.#%]*(\?\S+)?)?)?)@';
    return preg_replace_callback($re, function ($matches) {
        return sprintf('<a href="%s" target="_blank">%s</a>', trim($matches[0], '&quot;'), $matches[0]);
    }, $str);
}

/**
 * 二维数组排序
 * @param array $array 数组
 * @param string $field 排序字段
 * @param int $sort 排序方式
 * @return array
 */
function arraysSort(array $array, $field, $sort = SORT_DESC)
{
    array_multisort(array_column($array, $field), $sort, $array);
    return $array;
}