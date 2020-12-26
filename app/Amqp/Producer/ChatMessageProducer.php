<?php
declare(strict_types=1);
/**
 *
 * This is my open source code, please do not use it for commercial applications.
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author Yuandong<837215079@qq.com>
 * @link   https://github.com/gzydong/hyperf-chat
 */

namespace App\Amqp\Producer;

use App\Constants\SocketConstants;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;
use Hyperf\Utils\Str;

/**
 * 消息生产者
 *
 * @package App\Amqp\Producer
 */
class ChatMessageProducer extends ProducerMessage
{
    /**
     * 交换机类型
     *
     * @var string
     */
    public $type = Type::FANOUT;

    /**
     * 交换机名称
     *
     * @var string
     */
    public $exchange = SocketConstants::CONSUMER_MESSAGE_EXCHANGE;

    /**
     * 实例化处理
     *
     * @param string $event 事件名
     * @param array $data 数据
     * @param array $options 其它参数
     */
    public function __construct(string $event, array $data, array $options = [])
    {
        $message = [
            'uuid' => $this->uuid(),// 自定义消息ID
            'event' => $event,
            'data' => $data,
            'options' => $options
        ];

        $this->payload = $message;
    }

    /**
     * 生成唯一的消息ID
     *
     * @return string
     */
    private function uuid()
    {
        return Str::random() . mt_rand(100000, 999999) . uniqid();
    }
}
