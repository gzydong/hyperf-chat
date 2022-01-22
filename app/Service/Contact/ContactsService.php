<?php
/**
 * This is my open source code, please do not use it for commercial applications.
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author Yuandong<837215079@qq.com>
 * @link   https://github.com/gzydong/hyperf-chat
 */

namespace App\Service\Contact;

use App\Repository\Contact\ContactRepository;
use App\Service\BaseService;
use App\Traits\PagingTrait;

/**
 * ContactsService
 * 注：联系人服务层
 *
 * @package App\Service
 */
class ContactsService extends BaseService
{
    use PagingTrait;

    /**
     * @var ContactRepository
     */
    private $repository;

    /**
     * @param ContactRepository $repository
     */
    public function __construct(ContactRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 删除联系人
     *
     * @param int $user_id   用户ID
     * @param int $friend_id 好友ID
     * @return bool
     */
    public function delete(int $user_id, int $friend_id): bool
    {
        $isTrue = (bool)$this->repository->update([
            "user_id"   => $user_id,
            "friend_id" => $friend_id,
            "status"    => 1,
        ], ['status' => 0]);

        if ($isTrue) redis()->del("good_friends:{$user_id}_{$friend_id}");

        return $isTrue;
    }

    /**
     * 编辑联系人备注
     *
     * @param int    $user_id   用户ID
     * @param int    $friend_id 好友ID
     * @param string $remark    好友备注名称
     * @return bool
     */
    public function editRemark(int $user_id, int $friend_id, string $remark): bool
    {
        return (bool)$this->repository->update([
            "user_id"   => $user_id,
            "friend_id" => $friend_id,
        ], ['remark' => $remark]);
    }
}
