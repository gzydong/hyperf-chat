<?php
declare(strict_types=1);

namespace App\Listener;

use App\Helper\RegularHelper;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\Event\ValidatorFactoryResolved;
use Hyperf\Event\Annotation\Listener;

/**
 * @Listener
 */
class ValidatorFactoryResolvedListener implements ListenerInterface
{

    public function listen(): array
    {
        return [
            ValidatorFactoryResolved::class,
        ];
    }

    public function process(object $event)
    {
        /**  @var ValidatorFactoryInterface $validatorFactory */
        $validatorFactory = $event->validatorFactory;

        // 注册了 ids 验证器(验证英文逗号拼接的整形数字字符串 例如:[1,2,3,4,5])
        $validatorFactory->extend('ids', function ($attribute, $value) {
            return is_string($value) && (empty($value) || RegularHelper::verify('ids', $value));
        });

        // 注册手机号验证器
        $validatorFactory->extend('phone', function ($attribute, $value) {
            return is_string($value) && RegularHelper::verify('phone', $value);
        });
    }
}
