<?php
declare(strict_types=1);

namespace App\Exception\Handler;

use App\Cache\Repository\LockRedis;
use App\Constant\ResponseCode;
use App\Template\MailerTemplate;
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

        $isDev = config('app_env') == 'dev';

        $data = json_encode([
            'code'   => ResponseCode::SERVER_ERROR,
            'error'  => $isDev ? $throwable->getMessage() : 'Internal Server Error.',
            'traces' => $isDev ? $throwable->getLine() : [],
        ], JSON_UNESCAPED_UNICODE);

        // 错误信息记录日志
        logger()->error($throwable->getTraceAsString());

        !$isDev && $this->sendAdminEmail($throwable);

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
                    di()->get(MailerTemplate::class)->errorNotice($throwable)
                );
            } catch (\Exception $exception) {

            }
        }
    }
}
