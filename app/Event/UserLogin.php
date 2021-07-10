<?php

namespace App\Event;

use App\Model\User;
use Hyperf\HttpServer\Contract\RequestInterface;

class UserLogin
{
    /**
     * @var User
     */
    public $user;

    /**
     * 登录平台
     *
     * @var string
     */
    public $platform;

    /**
     * IP地址
     *
     * @var string
     */
    public $ip;

    /**
     * UserLogin constructor.
     *
     * @param RequestInterface $request
     * @param User             $user
     */
    public function __construct(RequestInterface $request, User $user)
    {
        $this->user = $user;

        $this->platform = $request->input('platform', '');

        $this->ip = $request->getServerParams()['remote_addr'];
    }
}
