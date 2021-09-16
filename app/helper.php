<?php
/*
|--------------------------------------------------------------------------
| Common function method
|--------------------------------------------------------------------------
*/

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Database\Query\Builder as QueryBuilder;
use Hyperf\Database\Model\Builder as ModelBuilder;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Server\ServerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Websocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;
use Hyperf\Utils\Str;
use Hyperf\Redis\Redis;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * 容器实例
 *
 * @return ContainerInterface
 */
function di(): ContainerInterface
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
    return di()->get(Redis::class);
}

/**
 * Server 实例 基于 Swoole Server
 *
 * @return \Swoole\Coroutine\Server|\Swoole\Server
 */
function server()
{
    return di()->get(ServerFactory::class)->getServer()->getServer();
}

/**
 * WebSocket frame 实例
 *
 * @return mixed|Frame
 */
function frame()
{
    return di()->get(Frame::class);
}

/**
 * WebSocketServer 实例
 *
 * @return mixed|WebSocketServer
 */
function websocket()
{
    return di()->get(WebSocketServer::class);
}

/**
 * 缓存实例 简单的缓存
 *
 * @return mixed|\Psr\SimpleCache\CacheInterface
 */
function cache()
{
    return di()->get(Psr\SimpleCache\CacheInterface::class);
}

/**
 * Dispatch an event and call the listeners.
 *
 * @return mixed|\Psr\EventDispatcher\EventDispatcherInterface
 */
function event()
{
    return di()->get(Psr\EventDispatcher\EventDispatcherInterface::class);
}

/**
 * 控制台日志
 *
 * @return StdoutLoggerInterface|mixed
 */
function stdout_log()
{
    return di()->get(StdoutLoggerInterface::class);
}

/**
 * 文件日志
 *
 * @param string $name
 * @return LoggerInterface
 */
function logger(string $name = 'APP'): LoggerInterface
{
    return di()->get(LoggerFactory::class)->get($name);
}

/**
 * Http 请求实例
 *
 * @return mixed|ServerRequestInterface
 */
function request()
{
    return di()->get(ServerRequestInterface::class);
}

/**
 * 请求响应
 *
 * @return ResponseInterface|mixed
 */
function response()
{
    return di()->get(ResponseInterface::class);
}

/**
 * 获取邮件助手
 *
 * @return \App\Support\Mailer|mixed
 */
function email()
{
    return di()->get(App\Support\Mailer::class);
}

/**
 * 获取媒体文件url
 *
 * @param string $path 文件相对路径
 * @return string
 */
function get_media_url(string $path): string
{
    return sprintf('%s/%s', rtrim(config('domain.img_url'), '/'), ltrim($path, '/'));
}

/**
 * 随机生成图片名
 *
 * @param string $ext      图片后缀名
 * @param array  $filesize 图片文件大小信息
 * @return string
 */
function create_image_name(string $ext, array $filesize): string
{
    return uniqid() . Str::random() . '_' . $filesize[0] . 'x' . $filesize[1] . '.' . $ext;
}

/**
 * 判断0或正整数
 *
 * @param string|int $value  验证字符串
 * @param bool       $isZero 判断是否可为0
 * @return bool
 */
function check_int($value, $isZero = false): bool
{
    $reg = $isZero ? '/^[+]{0,1}(\d+)$/' : '/^[1-9]\d*$/';
    return is_numeric($value) && preg_match($reg, $value);
}

/**
 * 解析英文逗号',' 拼接的 ID 字符串
 *
 * @param string|int $ids 字符串(例如; "1,2,3,4,5,6")
 * @return array
 */
function parse_ids($ids): array
{
    return array_unique(explode(',', trim($ids)));
}

/**
 * 推送消息到 Redis 订阅中
 *
 * @param string       $chan
 * @param string|array $message
 */
function push_redis_subscribe(string $chan, $message)
{
    redis()->publish($chan, is_string($message) ? $message : json_encode($message));
}

/**
 * 生成随机文件名
 *
 * @param string $ext 文件后缀名
 * @return string
 */
function create_random_filename(string $ext): string
{
    $ext = $ext ? '.' . $ext : '';
    return Str::random(10) . uniqid() . $ext;
}

/**
 * 获取客户端你真实IP
 *
 * @return mixed|string
 */
function get_real_ip(): string
{
    if ($ip = request()->getHeaderLine('x-real-ip')) {
        return $ip;
    } else if ($ip = request()->getHeaderLine('x-forwarded-for')) {
        return $ip;
    }

    return request()->getServerParams()['remote_addr'] ?? '';
}


/**
 * 通过查询构造器读取分页数据
 *
 * @param QueryBuilder|ModelBuilder $model  查询构造器
 * @param array                     $fields 查询字段
 * @param int                       $page   当前分页
 * @param int                       $size   分页大小
 * @return array
 */
function toPaginate($model, array $fields = ['*'], int $page = 1, int $size = 15): array
{
    $total = $model->count();

    $data = [
        'rows'     => [],
        'paginate' => [
            'page'  => $page,
            'size'  => $size,
            'total' => $total,
        ]
    ];

    if ($total > 0) $data['rows'] = $model->forPage($page, $size)->get($fields)->toArray();

    if ($data['rows'] && $model instanceof QueryBuilder) {
        foreach ($data['rows'] as &$row) {
            $row = (array)$row;
        }
    }

    return $data;
}
