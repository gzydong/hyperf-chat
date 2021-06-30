<?php

declare(strict_types=1);

namespace App\Command;

use App\Cache\FriendRemark;
use App\Cache\LastMessage;
use App\Cache\Repository\HashRedis;
use App\Cache\Repository\ListRedis;
use App\Cache\Repository\LockRedis;
use App\Cache\Repository\HashGroupRedis;
use App\Cache\Repository\SetGroupRedis;
use App\Cache\Repository\SetRedis;
use App\Cache\Repository\StreamRedis;
use App\Cache\Repository\StringRedis;
use App\Cache\Repository\ZSetRedis;
use App\Cache\SocketFdBindUser;
use App\Cache\SocketRoom;
use App\Cache\SocketUserBindFds;
use App\Cache\UnreadTalk;
use App\Model\Group\Group;
use App\Model\Group\GroupMember;
use App\Service\TalkService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use League\Flysystem\Filesystem;
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
        //$lock = LockRedis::getInstance();
        //var_dump($lock->delete('ttt'));
        //var_dump($lock->lock('ttt', 180, 5));

        //$string = StringRedis::getInstance();
        //var_dump($string->set('yuandong', 'dong', 30));
        //var_dump($string->ttl('yuandong'));
        //var_dump($string->isExist('yuandong'));

        //$hash = HashRedis::getInstance();
        //for ($i = 0; $i < 10; $i++) {
        //    $hash->add('user:' . $i, (string)rand(0, 100));
        //}
        //var_dump($hash->count());
        //var_dump($hash->all());
        //var_dump($hash->isMember('user:1'));
        //var_dump($hash->rem('user:3'));
        //var_dump($hash->get('user:6','user:7'));
        //$hash->incr('user:6',11);
        //var_dump($hash->get('user:6'));

        //$list = ListRedis::getInstance();
        //$list->push('1','2','3','4','5');
        //var_dump($list->all());
        //var_dump($list->clear());
        //var_dump($list->count());

        //$set = SetRedis::getInstance();
        //$set->add('user1','user:2','user:3');
        //var_dump($set->all());
        //var_dump($set->count());
        //var_dump($set->isMember('user:3'));
        //var_dump($set->randMember(2));

        //$zset = ZSetRedis::getInstance();
        //for ($i = 1; $i < 100; $i++) {
        //    $zset->add('user:' . $i, $i);
        //}
        //$zset->delete();
        //var_dump($zset->count());
        //var_dump($zset->rank(2,10));
        //var_dump($zset->getMemberScore('user:2'));
        //var_dump($zset->getMemberRank('user:2'));
        //var_dump($zset->rank());
        //var_dump($zset->range('20','60'));

        //$stream = StreamRedis::getInstance();
        //var_dump($stream->info());
        //for ($i = 0; $i < 10; $i++) {
        //    $stream->add([
        //        'user_id' => $i,
        //        'time'    => time()
        //    ]);
        //}
        //
        //$stream->run(function (string $id, array $data): bool {
        //    echo PHP_EOL . " 消息ID: {$id} , 任务数据: " . json_encode($data);
        //
        //    return true;
        //}, 'default', 'default');


        //FriendRemark::getInstance()->reload();

        //LastMessage::getInstance()->save(2, 1, 3, [
        //    'created_at' => date('Y-m-d H:i:s'),
        //    'content'    => '那三级卡那那可是那那会计师哪安顺科技那发'
        //]);
        //var_dump(LastMessage::getInstance()->read(3, 6, 3));

        //var_dump(UnreadTalk::getInstance()->read(1, 2));
        //UnreadTalk::getInstance()->save(1, 2);

        //$talk = UnreadTalk::getInstance();
        //for ($i = 1; $i < 10; $i++) {
        //    for ($j = 1; $j < 10; $j++) {
        //        $talk->increment($i, $j);
        //    }
        //}

        //$model = new TalkService();
        //$model->talks(2054);

        //var_dump(FriendRemark::getInstance()->read(2054,2055));

        //$socketRoom = SocketRoom::getInstance();
        //$socketRoom->addRoomMember('');
        //$keys = redis()->keys('rds-set*');
        //foreach ($keys as $key) {
        //    redis()->del($keys);
        //}

        //SocketFdBindUser::getInstance()->bind(1, 2054);
        //SocketUserBindFds::getInstance()->bind(1, 2054);

        //$model1 = SocketUserBindFds::getInstance();
        //$model2 = FriendRemark::getInstance();
        //
        //var_dump($model1 === SocketUserBindFds::getInstance());
        //var_dump($model2 === FriendRemark::getInstance());
        //
        //var_dump(SocketUserBindFds::getInstance());
        //var_dump(FriendRemark::getInstance());

        //SocketUserBindFds::getInstance();
        //SocketUserBindFds::getInstance();
        //SocketRoom::getInstance();
        //FriendRemark::getInstance();
        //SocketUserBindFds::getInstance();
        //SocketRoom::getInstance();
        //FriendRemark::getInstance();

        //var_dump(SocketUserBindFds::getInstance());
        //var_dump(SocketRoom::getInstance());
        //var_dump(FriendRemark::getInstance());
        //
        //var_dump('------');
        //var_dump(SocketUserBindFds::getInstance());
        //var_dump(SocketRoom::getInstance());
        //var_dump(FriendRemark::getInstance());

        //var_dump(Group::isManager(2054,116));

        var_dump(pathinfo('spring-boot相关',PATHINFO_EXTENSION));
    }
}
