<?php
declare(strict_types=1);

namespace App\Listener;

use App\Service\TalkMessageService;
use Hyperf\Event\Contract\ListenerInterface;
use App\Event\LoginEvent;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Guzzle\ClientFactory;

/**
 * @Listener
 */
class LoginListener implements ListenerInterface
{
    public function listen(): array
    {
        // 返回一个该监听器要监听的事件数组，可以同时监听多个事件
        return [
            LoginEvent::class,
        ];
    }

    /**
     * @param object|LoginEvent $event
     */
    public function process(object $event)
    {
        //$reason  = '正常登录';
        //$address = '';
        //
        //$api = config('juhe_api.ip');
        //
        //$client = di()->get(ClientFactory::class)->create([]);
        //$params = [
        //    'ip'  => $event->ip,
        //    'key' => $api['key'],
        //];
        //
        //$response = $client->get($api['api'] . '?' . http_build_query($params));
        //if ($response->getStatusCode() == 200) {
        //    $result = json_decode($response->getBody()->getContents(), true);
        //    if ($result['resultcode'] == 200) {
        //        unset($result['result']['Isp']);
        //        $address = join(' ', $result['result']);
        //    }
        //}
        //
        //di()->get(TalkMessageService::class)->insertLoginMessage([
        //    'user_id' => $event->user->id,
        //], [
        //    'user_id'  => $event->user->id,
        //    'ip'       => $event->ip,
        //    'platform' => $event->platform,
        //    'agent'    => $event->agent,
        //    'address'  => $address,
        //    'reason'   => $reason,
        //]);
    }
}
