<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware\UnauthorizedHandler;

use App\Middleware\UnauthorizedHandler\RefererRedirectHandler;
use Authorization\Exception\Exception;
use Authorization\Exception\ForbiddenException;
use Authorization\Exception\MissingIdentityException;
use Authorization\Policy\Result;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\ServerRequest as PsrRequest;

/**
 * RefererRedirectHandler Test Case
 *
 * @uses \App\Middleware\UnauthorizedHandler\RefererRedirectHandler
 */
class RefererRedirectHandlerTest extends TestCase
{
    protected RefererRedirectHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new RefererRedirectHandler();
    }

    /**
     * MissingIdentityException should trigger a redirect.
     */
    public function testHandleMissingIdentityRedirects(): void
    {
        $exception = new MissingIdentityException('Not logged in.');
        $request = new ServerRequest([
            'environment' => [
                'HTTP_REFERER' => 'http://localhost/admin/users',
                'HTTP_HOST' => 'localhost',
            ],
        ]);

        $response = $this->handler->handle($exception, $request);

        $this->assertSame(302, $response->getStatusCode());
    }

    /**
     * When there is no Referer header, should redirect to '/'.
     */
    public function testHandleRedirectsToRootWhenNoReferer(): void
    {
        $result = new Result(false, 'No access.');
        $exception = new ForbiddenException($result);
        $request = new ServerRequest();

        $response = $this->handler->handle($exception, $request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/', $response->getHeaderLine('Location'));
    }

    /**
     * An exception not in the configured list should be re-thrown.
     */
    public function testHandleRethrowsUnknownException(): void
    {
        $this->expectException(Exception::class);

        $exception = new Exception('Unexpected');
        $request = new ServerRequest();
        $this->handler->handle($exception, $request, [
            'exceptions' => [ForbiddenException::class],
        ]);
    }

    /**
     * A non-CakePHP PSR-7 request should redirect to '/' without flash.
     */
    public function testHandleWithPsrRequestRedirectsToRoot(): void
    {
        $result = new Result(false, 'Denied.');
        $exception = new ForbiddenException($result);
        $request = new PsrRequest();

        $response = $this->handler->handle($exception, $request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/', $response->getHeaderLine('Location'));
    }

    /**
     * Custom statusCode option should be respected.
     */
    public function testHandleRespectsCustomStatusCode(): void
    {
        $result = new Result(false, 'Denied.');
        $exception = new ForbiddenException($result);
        $request = new ServerRequest();

        $response = $this->handler->handle($exception, $request, ['statusCode' => 301]);

        $this->assertSame(301, $response->getStatusCode());
    }
}
