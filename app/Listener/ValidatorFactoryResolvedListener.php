<?php

namespace App\Listener;

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
            if (!is_string($value)) return false;

            foreach (explode(',', $value) as $id) {
                if (!check_int($id)) return false;
            }

            return true;
        });

        // 注册手机号验证器
        $validatorFactory->extend('phone', function ($attribute, $value) {
            if (!is_string($value)) return false;

            return (bool)preg_match('/^1[3456789][0-9]{9}$/', $value);
        });
    }
}
