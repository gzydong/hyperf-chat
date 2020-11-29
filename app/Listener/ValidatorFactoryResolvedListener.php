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
        $validatorFactory->extend('ids', function ($attribute, $value, $parameters, $validator) {
            $arr = explode(',', $value);
            foreach ($arr as $id) {
                if (!check_int($id)) return false;
            }

            return true;
        });
    }
}
