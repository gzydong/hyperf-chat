<?php
declare(strict_types=1);

namespace App\Event;

use App\Model\User;
use Hyperf\HttpServer\Contract\RequestInterface;

class LoginEvent
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
     * @var string
     */
    public $agent;

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

        $this->agent = $request->getHeaderLine('user-agent');

        $this->ip = get_real_ip();
    }
}
