<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\User;

/**
 * User 数据层
 */
class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * 根据主键查询
     *
     * @param int $user_id
     * @return \Hyperf\Database\Model\Model
     */
    public function findById(int $user_id)
    {
        return $this->find($user_id);
    }

    /**
     * 根据手机号查询下用户信息
     *
     * @param string $mobile 手机号
     * @param array  $fields 查询字段
     * @return array
     */
    public function findByMobile(string $mobile, array $fields = ["*"]): array
    {
        return $this->first(["mobile" => $mobile], $fields, true);
    }

    /**
     * 查询手机号是否存在
     *
     * @param string $mobile 手机号
     *
     * @return bool
     */
    public function isExistMobile(string $mobile): bool
    {
        return $this->exists(["mobile" => $mobile]);
    }
}
