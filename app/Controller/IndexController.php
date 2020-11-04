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

class IndexController extends AbstractController
{
    public function index(ResponseInterface $response)
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        $this->validate($this->request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

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
