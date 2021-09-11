<?php
declare(strict_types=1);

namespace App\Service\Group;

use Exception;
use App\Model\Group\GroupNotice;
use App\Service\BaseService;

class GroupNoticeService extends BaseService
{
    public function create(int $user_id, array $params)
    {
        return GroupNotice::create([
            'group_id'   => $params['group_id'],
            'creator_id' => $user_id,
            'title'      => $params['title'],
            'content'    => $params['content'],
            'is_top'     => $params['is_top'],
            'is_confirm' => $params['is_confirm'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function update(array $params)
    {
        return GroupNotice::where('id', $params['notice_id'])->update([
            'title'      => $params['title'],
            'content'    => $params['content'],
            'is_top'     => $params['is_top'],
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 删除群公告
     *
     * @param int $notice_id 公告ID
     * @param int $user_id   用户ID
     * @return bool
     * @throws Exception
     */
    public function delete(int $notice_id, int $user_id): bool
    {
        $notice = GroupNotice::where('id', $notice_id)->first();

        if (!$notice) return false;

        // 判断用户是否是管理员
        if (!di()->get(GroupMemberService::class)->isAuth($notice->group_id, $user_id)) {
            throw new Exception('非管理员，无法进行操作！', 403);
        }

        $notice->is_delete  = 1;
        $notice->deleted_at = date('Y-m-d H:i:s');
        $notice->save();

        return true;
    }

    /**
     * 获取群公告列表
     *
     * @param int $group_id 群ID
     * @return array
     */
    public function lists(int $group_id): array
    {
        return GroupNotice::leftJoin('users', 'users.id', '=', 'group_notice.creator_id')
            ->where([
                ['group_notice.group_id', '=', $group_id],
                ['group_notice.is_delete', '=', 0]
            ])
            ->orderBy('group_notice.is_top', 'desc')
            ->orderBy('group_notice.updated_at', 'desc')
            ->get([
                'group_notice.id',
                'group_notice.creator_id',
                'group_notice.title',
                'group_notice.content',
                'group_notice.is_top',
                'group_notice.is_confirm',
                'group_notice.confirm_users',
                'group_notice.created_at',
                'group_notice.updated_at',
                'users.avatar',
                'users.nickname',
            ])->toArray();
    }
}
