<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\Cache\Repository\LockRedis;
use App\Constants\ResponseCode;
use App\Support\MailerTemplate;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        $this->logger->error($throwable->getTraceAsString());

        $data = json_encode([
            'code'    => ResponseCode::SERVER_ERROR,
            'message' => 'Internal Server Error.',
            'errors'  => config('app_env') == 'dev' ? $throwable->getTrace() : [],
        ], JSON_UNESCAPED_UNICODE);

        $this->sendAdminEmail($throwable);

        return $response->withHeader('Server', 'Lumen IM')->withStatus(500)->withBody(new SwooleStream($data));
    }

    /**
     * @param Throwable $throwable
     * @return bool
     */
    public function isValid(Throwable $throwable): bool
    {
        return true;
    }

    /**
     * 发送系统报错通知邮件
     *
     * @param Throwable $throwable
     */
    public function sendAdminEmail(Throwable $throwable)
    {
        if (config('app_env') != 'dev') {
            return;
        }

        $error = implode(':', [
            'error',
            md5($throwable->getFile() . $throwable->getCode() . $throwable->getLine()),
        ]);

        $adminEmail = config('admin_email');
        if ($adminEmail && LockRedis::getInstance()->lock($error, 60 * 30)) {
            try {
                email()->send(
                    $adminEmail,
                    '系统报错通知',
                    container()->get(MailerTemplate::class)->errorNotice($throwable)
                );
            } catch (\Exception $exception) {

            }
        }
    }
}
