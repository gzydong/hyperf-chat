<?php

declare(strict_types=1);

namespace App\Command;

use App\Cache\VoteStatisticsCache;
use App\Model\Talk\TalkRecordsVote;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Hyperf\Guzzle\ClientFactory;
use function _HumbugBox39a196d4601e\RingCentral\Psr7\build_query;

/**
 * @Command
 */
class TestCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('test:command');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {
        //VoteStatisticsCache::getInstance()->updateVoteCache(15);

        $api     = config('juhe_api.ip');
        $options = [];
        $client  = di()->get(ClientFactory::class)->create($options);
        $params  = [
            'ip'  => '47.105.180.123',
            'key' => $api['key'],
        ];

        $address  = '';
        $response = $client->get($api['api'] . '?' . http_build_query($params));
        if ($response->getStatusCode() == 200) {
            $result = json_decode($response->getBody()->getContents(), true);
            if ($result['resultcode'] == 200) {
                unset($result['result']['Isp']);
                $address = join(' ', $result['result']);
            }
        }

        var_dump($address);
    }
}
