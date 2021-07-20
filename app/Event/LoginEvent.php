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
     * UserLogin constructor.
     *
     * @param RequestInterface $request
     * @param User             $user
     */
    public function __construct(RequestInterface $request, User $user)
    {
        $this->user = $user;

        $this->platform = $request->input('platform', '');

        $this->ip = $this->getClientRealIp($request);
    }

    /**
     * 获取用户真实 IP
     *
     * @param RequestInterface $request
     * @return mixed
     */
    private function getClientRealIp(RequestInterface $request)
    {
        $params = $request->getServerParams();

        $real_ip = $params["remote_addr"];

        if (isset($params["http_x_forwarded_for"])) {
            $real_ip = $params["http_x_forwarded_for"];
        } else if (isset($params["HTTP_CLIENT_IP"])) {
            $real_ip = $params["http_client_ip"];
        }

        return $real_ip;
    }
}
