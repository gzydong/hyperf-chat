<?php
declare(strict_types=1);

namespace App\Service;

use App\Helper\HashHelper;
use App\Model\Robot;
use App\Model\User;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Str;

class RobotService
{
    /**
     * 创建机器人
     *
     * @param array $data
     * @return bool|array
     */
    public function create(array $data)
    {
        Db::beginTransaction();
        try {
            $user = User::create([
                'mobile'   => '100' . mt_rand(1000, 9999) . mt_rand(1000, 9999),
                "nickname" => "登录助手",
                'password' => HashHelper::make(Str::random(10)),
                'is_robot' => 1
            ]);

            $robot = Robot::create([
                'user_id'    => $user->id,
                'robot_name' => $data['robot_name'],
                'describe'   => $data['describe'],
                'logo'       => $data['logo'],
                'is_talk'    => $data['is_talk'],
                'type'       => $data['type'],
                'status'     => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            return false;
        }

        return $robot->toArray();
    }
}
