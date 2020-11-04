<?php

declare(strict_types=1);

/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use App\Exception\ValidateException;
use App\Constants\ResponseCode;

abstract class AbstractController
{
    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Inject
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @Inject
     * @var ValidatorFactoryInterface
     */
    protected $validationFactory;

    /**
     * 自定义验证器
     *
     * @param mixed ...$arg
     */
    protected function validate(...$arg)
    {
        $validator = $this->validationFactory->make(...$arg);
        if ($validator->fails()) {
            throw new ValidateException($validator->errors()->first(), ResponseCode::VALIDATION_ERROR);
        }
    }
}
