<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Robot;

class RobotRepository extends BaseRepository
{
    public function __construct(Robot $model)
    {
        parent::__construct($model);
    }

    /**
     * 通过机器人类型获取关联ID
     *
     * @param int $type
     * @return int
     */
    public function findTypeByUserId(int $type): int
    {
        return (int)$this->value(['type' => $type], 'user_id');
    }
}
