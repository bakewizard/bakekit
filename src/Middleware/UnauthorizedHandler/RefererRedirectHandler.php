<?php
declare(strict_types=1);

namespace App\Middleware\UnauthorizedHandler;

use Authorization\Exception\Exception;
use Authorization\Exception\ForbiddenException;
use Authorization\Exception\MissingIdentityException;
use Authorization\Middleware\UnauthorizedHandler\HandlerInterface;
use Cake\Http\Response;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This handler will redirect the response if one of configured exception classes is encountered.
 */
class RefererRedirectHandler implements HandlerInterface
{
    /**
     * Default config:
     *
     *  - `exceptions` - A list of exception classes.
     *  - `queryParam` - Query parameter name for the target url.
     *  - `statusCode` - Redirection status code.
     *
     * @var array
     */
    protected array $defaultOptions = [
        'exceptions' => [
            MissingIdentityException::class,
            ForbiddenException::class,
        ],
        'queryParam' => 'redirect',
        'statusCode' => 302,
    ];

    /**
     * Handles the unauthorized request. The modified response should be returned.
     *
     * @param \Authorization\Exception\Exception $exception Authorization exception thrown by the application.
     * @param \Psr\Http\Message\ServerRequestInterface $request Server request.
     * @param array $options Options array.
     * @return \Psr\Http\Message\ResponseInterface
     */
    #[Override]
    public function handle(Exception $exception, ServerRequestInterface $request, array $options = []): ResponseInterface
    {
        $options += $this->defaultOptions;

        if (!$this->checkException($exception, $options['exceptions'])) {
            throw $exception;
        }

        $message = $exception instanceof ForbiddenException ? $exception->getResult()->getReason() : $exception->getMessage();

        $request->getFlash()->error($message, ['params' => ['code' => $exception->getCode()]]);

        return (new Response())
                        ->withHeader('Location', $request->referer())
                        ->withStatus($options['statusCode']);
    }

    /**
     * Checks if an exception matches one of the classes.
     *
     * @param \Authorization\Exception\Exception $exception Exception instance.
     * @param array $exceptions A list of exception classes.
     * @return bool
     */
    protected function checkException(Exception $exception, array $exceptions): bool
    {
        foreach ($exceptions as $class) {
            if ($exception instanceof $class) {
                return true;
            }
        }

        return false;
    }
}
