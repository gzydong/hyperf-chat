<?php
/**
 * This is my open source code, please do not use it for commercial applications.
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author Yuandong<837215079@qq.com>
 * @link   https://github.com/gzydong/hyperf-chat
 */

namespace App\Service;

use App\Model\User;
use App\Model\UsersFriend;
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
     * 删除联系人
     *
     * @param int $user_id   用户ID
     * @param int $friend_id 好友ID
     * @return bool
     */
    public function delete(int $user_id, int $friend_id): bool
    {
        $res = (bool)UsersFriend::where('user_id', $user_id)->where('friend_id', $friend_id)->where('status', 1)->update([
            'status'     => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($res) redis()->del("good_friends:{$user_id}_{$friend_id}");

        return $res;
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
        return (bool)UsersFriend::where('user_id', $user_id)->where('friend_id', $friend_id)->update([
            'remark'     => $remark,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 搜索联系人
     *
     * @param string $mobile 用户手机号/登录账号
     * @return array
     */
    public function findContact(string $mobile): array
    {
        $user = User::where('mobile', $mobile)->first(['id', 'nickname', 'mobile', 'avatar', 'gender']);

        return $user ? $user->toArray() : [];
    }
}
