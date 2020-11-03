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

use Hyperf\HttpServer\Contract\ResponseInterface;

use Hyperf\Amqp\Producer;
use App\Amqp\Producer\DemoProducer;

class IndexController extends AbstractController
{
    public function index(ResponseInterface $response)
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        $producer = container()->get(Producer::class);
        $producer->produce(new DemoProducer('test'. date('Y-m-d H:i:s')));

        return [
            'method' => $method,
            'message' => "Hello {$user}."
        ];
    }
}
