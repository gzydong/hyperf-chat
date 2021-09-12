<?php
declare(strict_types=1);

namespace App\Support;

use Hyperf\Guzzle\ClientFactory;

class IpAddress
{
    /**
     * 获取IP地址信息
     *
     * @param string $ip
     * @return array
     */
    public function get(string $ip): array
    {
        return $this->request($ip);
    }

    /**
     * 请求聚合数据IP查询接口
     *
     * @param string $ip
     * @return array
     */
    private function request(string $ip): array
    {
        $api = config('juhe_api.ip');

        $client = di()->get(ClientFactory::class)->create([]);
        $params = [
            'ip'  => $ip,
            'key' => $api['key'],
        ];

        $response = $client->get($api['api'] . '?' . http_build_query($params));
        if ($response->getStatusCode() == 200) {
            $result = json_decode($response->getBody()->getContents(), true);

            if ($result['resultcode'] != '200') {
                return [];
            }

            $result = $result['result'];

            return [
                'country'  => $result['Country'] ?? '',
                'province' => $result['Province'] ?? '',
                'city'     => $result['City'] ?? '',
                'isp'      => $result['Isp'] ?? '',
                'ip'       => $ip,
            ];
        }

        return [];
    }
}
