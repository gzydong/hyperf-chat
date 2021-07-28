<?php
declare(strict_types=1);

namespace App\Service\Group;

use App\Model\Group\GroupMember;
use App\Service\BaseService;

class GroupMemberService extends BaseService
{
    /**
     * 判断群成员权限
     *
     * @param int $group_id 群组ID
     * @param int $user_id  用户ID
     * @param int $leader   权限角色
     * @return bool
     */
    public function isAuth(int $group_id, int $user_id, int $leader = 2): bool
    {
        return GroupMember::query()->where([
            'group_id' => $group_id,
            'user_id'  => $user_id,
            'leader'   => $leader,
            'is_quit'  => 0,
        ])->exists();
    }

    /**
     * 判断用户是否是群成员
     *
     * @param int $group_id 群ID
     * @param int $user_id  用户ID
     * @return bool
     */
    public function isMember(int $group_id, int $user_id): bool
    {
        return GroupMember::where('group_id', $group_id)->where('user_id', $user_id)->where('is_quit', 0)->exists();
    }

    /**
     * 获取所有群成员ID
     *
     * @param int $group_id 群组ID
     * @return array
     */
    public function getMemberIds(int $group_id): array
    {
        return GroupMember::query()->where('group_id', $group_id)->where('is_quit', 0)->pluck('user_id')->toArray();
    }

    /**
     * 获取用户的所有群ID
     *
     * @param int $user_id 用户ID
     * @return array
     */
    public function getUserGroupIds(int $user_id): array
    {
        return GroupMember::query()->where('user_id', $user_id)->where('is_quit', 0)->pluck('group_id')->toArray();
    }

    /**
     * 获取群成员名片
     *
     * @param int $group_id 群组ID
     * @param int $user_id  用户ID
     * @return string
     */
    public function getVisitCard(int $group_id, int $user_id): string
    {
        return GroupMember::query()->where('group_id', $group_id)->where('user_id', $user_id)->value('user_card') ?? "";
    }
}
