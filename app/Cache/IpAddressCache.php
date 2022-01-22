<?php
declare(strict_types=1);

namespace App\Cache;

use App\Cache\Repository\HashRedis;
use App\Helper\JsonHelper;
use App\Support\IpAddress;

class IpAddressCache extends HashRedis
{
    protected $name = 'ip-address';

    public function getAddressInfo(string $ip)
    {
        $result = $this->get($ip);

        if (!empty($result)) {
            return JsonHelper::decode($result);
        }

        $result = di()->get(IpAddress::class)->get($ip);
        if (!empty($result)) {
            $this->add($ip, JsonHelper::encode($result));
        }

        return $result;
    }
}
