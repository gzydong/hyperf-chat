<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\UsersFriend;
use App\Repository\ExampleRepository;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;

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
        $repository = di()->get(ExampleRepository::class);

        $repository->case4();

        //$api     = config('juhe_api.ip');
        //$options = [];
        //$client  = di()->get(ClientFactory::class)->create($options);
        //$params  = [
        //    'ip'  => '47.105.180.123',
        //    'key' => $api['key'],
        //];
        //
        //$address  = '';
        //$response = $client->get($api['api'] . '?' . http_build_query($params));
        //if ($response->getStatusCode() == 200) {
        //    $result = json_decode($response->getBody()->getContents(), true);
        //    if ($result['resultcode'] == 200) {
        //        unset($result['result']['Isp']);
        //        $address = join(' ', $result['result']);
        //    }
        //}
        //
        //var_dump($address);
    }


    // 更新好友表数据
    public function updateData()
    {
        $max = UsersFriend::max('id');
        UsersFriend::where('id', '<=', $max)->chunk(1000, function ($rows) {
            $arr = [];
            foreach ($rows as $row) {
                $arr[] = [
                    'user_id'    => $row->friend_id,
                    'friend_id'  => $row->user_id,
                    'status'     => 1,
                    'remark'     => '',
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                ];
            }

            UsersFriend::insert($arr);
        });
    }
}
