<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model as CModel;

/**
 * 数据库模型 - 基础类
 *
 * @package App\Model
 */
abstract class BaseModel extends CModel
{
    /**
     * 关闭自动维护时间字段
     *
     * @var bool
     */
    public $timestamps = false;
}
