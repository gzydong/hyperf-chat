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

namespace App\Controller;

use App\Amqp\Producer\ChatMessageProducer;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Amqp\Producer;

class IndexController extends AbstractController
{
    public function index(ResponseInterface $response)
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        $producer = container()->get(Producer::class);

        $ip = config('ip_address');

        $string = time();
        $producer->produce(new ChatMessageProducer("我是来自[{$ip} 服务器的消息]，{$string}"));

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }

    public function upload(ResponseInterface $response)
    {
        return [
            'method' => 'upload',
        ];
    }
}
