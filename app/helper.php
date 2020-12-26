<?php
/**
 *
 * This is my open source code, please do not use it for commercial applications.
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author Yuandong<837215079@qq.com>
 * @link   https://github.com/gzydong/hyperf-chat
 */

/*
|--------------------------------------------------------------------------
| Common function method
|--------------------------------------------------------------------------
*/

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
 *
 * @return \Psr\Container\ContainerInterface
 */
function container()
{
    return ApplicationContext::getContainer();
}

/**
 * Redis 客户端实例
 *
 * @return Redis|mixed
 */
function redis()
{
    return container()->get(Redis::class);
}

/**
 * Server 实例 基于 Swoole Server
 *
 * @return \Swoole\Coroutine\Server|\Swoole\Server
 */
function server()
{
    return container()->get(ServerFactory::class)->getServer()->getServer();
}

/**
 * WebSocket frame 实例
 *
 * @return mixed|Frame
 */
function frame()
{
    return container()->get(Frame::class);
}

/**
 * WebSocketServer 实例
 *
 * @return mixed|WebSocketServer
 */
function websocket()
{
    return container()->get(WebSocketServer::class);
}

/**
 * 缓存实例 简单的缓存
 *
 * @return mixed|\Psr\SimpleCache\CacheInterface
 */
function cache()
{
    return container()->get(Psr\SimpleCache\CacheInterface::class);
}

/**
 * 控制台日志
 *
 * @return StdoutLoggerInterface|mixed
 */
function stdout_log()
{
    return container()->get(StdoutLoggerInterface::class);
}

/**
 * 文件日志
 *
 * @param string $name
 * @return \Psr\Log\LoggerInterface
 */
function logger(string $name = 'APP')
{
    return container()->get(LoggerFactory::class)->get($name);
}

/**
 * Http 请求实例
 *
 * @return mixed|ServerRequestInterface
 */
function request()
{
    return container()->get(ServerRequestInterface::class);
}

/**
 * 请求响应
 *
 * @return ResponseInterface|mixed
 */
function response()
{
    return container()->get(ResponseInterface::class);
}

/**
 * 从HTML文本中提取所有图片
 *
 * @param string $content HTML文本
 *
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
 * @param string $day1 日期1
 * @param string $day2 日期2
 *
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
 *
 * @return string
 */
function get_media_url(string $path)
{
    return sprintf('%s/%s', rtrim(config('domain.img_url'), '/'), ltrim($path,'/'));
}

/**
 * 随机生成图片名
 *
 * @param string $ext 图片后缀名
 * @param int $width 图片宽度
 * @param int $height 图片高度
 *
 * @return string
 */
function create_image_name(string $ext, int $width, int $height)
{
    return uniqid() . Str::random(16) . uniqid() . '_' . $width . 'x' . $height . '.' . $ext;
}

/**
 * 替换文本中的url 为 a标签
 *
 * @param string $str 字符串
 *
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
 *
 * @param array $array 数组
 * @param string $field 排序字段
 * @param int $sort 排序方式
 *
 * @return array
 */
function arraysSort(array $array, $field, $sort = SORT_DESC)
{
    array_multisort(array_column($array, $field), $sort, $array);
    return $array;
}

/**
 * 判断0或正整数
 *
 * @param string $int 验证字符串
 * @param bool $isZero 判断是否可为0
 *
 * @return bool
 */
function check_int($int, $isZero = false)
{
    $reg = $isZero ? '/^[+]{0,1}(\d+)$/' : '/^[1-9]\d*$/';
    return is_numeric($int) && preg_match($reg, $int);
}

/**
 * 解析英文逗号',' 拼接的 ID 字符串
 *
 * @param string $ids 字符串(例如; "1,2,3,4,5,6")
 *
 * @return array
 */
function parse_ids($ids)
{
    return array_unique(explode(',', trim($ids)));
}
