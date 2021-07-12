<?php

declare(strict_types=1);

namespace App\Command;

use App\Helper\Hash;
use App\Model\User;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;
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
        User::create([
            'mobile'   => '18798271181',
            'password' => Hash::make('asdfbasjhdfbasj'),
        ]);
    }
}
