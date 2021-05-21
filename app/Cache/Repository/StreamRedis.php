<?php

namespace App\Cache\Repository;

use Closure;
use App\Traits\StaticInstance;
use App\Cache\Contracts\StreamRedisInterface;

class StreamRedis extends AbstractRedis implements StreamRedisInterface
{
    protected $prefix = 'rds-stream';

    protected $name = 'default';

    /**
     * 添加消息
     *
     * @param array $messages 消息信息
     * @param int   $maxLen   消息队列最大长度
     * @param false $isApproximate
     * @return string
     */
    public function add(array $messages, $maxLen = 0, $isApproximate = false)
    {
        return $this->redis()->xAdd($this->getCacheKey(), '*', $messages, $maxLen, $isApproximate);
    }

    /**
     * 删除消息
     *
     * @param string ...$id 消息ID
     * @return int
     */
    public function rem(string ...$id)
    {
        return $this->redis()->xDel($this->getCacheKey(), $id);
    }

    /**
     * 消费者消息确认
     *
     * @param string $group 消费组
     * @param string $id    消息ID
     * @return int
     */
    public function ack(string $group, string $id)
    {
        return $this->redis()->xAck($this->getCacheKey(), $group, [$id]);
    }

    /**
     * 获取消息总数
     *
     * @return int
     */
    public function count()
    {
        return $this->redis()->xLen($this->getCacheKey());
    }

    /**
     * 获取所有消息
     *
     * @return array
     */
    public function all()
    {
        return $this->redis()->xRange($this->getCacheKey(), '-', '+');
    }

    /**
     * 清空消息队列
     *
     * @return bool
     */
    public function clear()
    {
        foreach ($this->all() as $k => $v) {
            $this->rem($k);
        }

        return true;
    }

    /**
     * 删除消息队列 KEY
     *
     * @return int
     */
    public function delete()
    {
        return $this->redis()->del($this->getCacheKey());
    }

    /**
     * 对消息队列进行修剪，限制长度
     *
     * @param int  $maxLen
     * @param bool $isApproximate
     * @return int
     */
    public function trim(int $maxLen, bool $isApproximate = false)
    {
        return $this->redis()->xTrim($this->getCacheKey(), $maxLen, $isApproximate);
    }

    public function group($operation, $group, $msgId = '', $mkStream = false)
    {
        return $this->redis()->xGroup($operation, $this->getCacheKey(), $group, $msgId, $mkStream);
    }

    public function pending($group, $start = null, $end = null, $count = null, $consumer = null)
    {
        return $this->redis()->xPending($this->getCacheKey(), $group, $start, $end, $count, $consumer);
    }

    /**
     * 查看队列信息
     *
     * @param string $operation [stream:队列信息，groups:消费组信息]
     * @return mixed
     */
    public function info(string $operation = 'stream')
    {
        return $this->redis()->xInfo($operation, $this->getCacheKey());
    }

    /**
     * 运行消息队列
     *
     * @param Closure $closure  闭包函数
     * @param string  $group    消费组
     * @param string  $consumer 消费者
     * @param int     $count    同时获取的任务个数
     */
    public function run(Closure $closure, string $group, string $consumer, $count = 1)
    {
        $this->group('create', $group, '0');

        while (true) {
            $tasks = $this->redis()->xReadGroup($group, $consumer, [$this->getCacheKey() => '>'], $count);
            if (empty($tasks)) {
                sleep(1);// 获取不到任务，延时一秒
                continue;
            }

            foreach ($tasks[$this->getCacheKey()] as $id => $task) {
                if ($closure($id, $task)) {
                    $this->ack($group, $id);
                }
            }
        }
    }
}
