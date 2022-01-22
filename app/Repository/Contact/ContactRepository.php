<?php
declare(strict_types=1);

namespace App\Repository\Contact;

use App\Model\Contact\Contact;
use App\Repository\BaseRepository;

class ContactRepository extends BaseRepository
{
    public function __construct(Contact $model)
    {
        parent::__construct($model);
    }

    /**
     * 获取所有朋友ID
     *
     * @param int $user_id 用户ID
     *
     * @return array
     */
    public function findAllFriendIds(int $user_id)
    {
        return $this->pluck(["user_id" => $user_id, "status" => 1], 'friend_id')->toArray();
    }

    /**
     * 获取联系人列表
     *
     * @param int $user_id 用户ID
     *
     * @return array
     */
    public function friends(int $user_id): array
    {
        return $this->get([
            'contact.user_id' => $user_id,
            'contact.status'  => 1,
            'join table'      => [
                ['users', 'users.id', '=', 'contact.friend_id', 'inner'],
            ]
        ], [
            'users.id',
            'users.nickname',
            'users.avatar',
            'users.motto',
            'users.gender',
            'contact.remark as friend_remark',
        ]);
    }
}
