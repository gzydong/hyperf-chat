<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

use Hyperf\Amqp\Producer;
use App\Amqp\Producer\DemoProducer;

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

        parent::__construct('demo:command');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {
        $producer = ApplicationContext::getContainer()->get(Producer::class);

        $result = $producer->produce(new DemoProducer('test'. date('Y-m-d H:i:s')));
    }
}
