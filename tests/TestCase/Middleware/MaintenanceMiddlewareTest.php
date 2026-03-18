<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use App\Middleware\MaintenanceMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * App\Middleware\MaintenanceMiddleware Test Case
 *
 * When maintenance mode is off — always passes through (handler is called).
 * When maintenance mode is on — handler is NOT called for blocked IPs.
 * isIpAllowed() CIDR logic is tested via a testable subclass.
 *
 * @uses \App\Middleware\MaintenanceMiddleware
 */
class MaintenanceMiddlewareTest extends TestCase
{
    private Response $response;
    private RequestHandlerInterface $handler;
    private bool $handlerCalled;

    protected function setUp(): void
    {
        parent::setUp();
        $this->response = new Response();
        $this->handlerCalled = false;

        $response = $this->response;
        $called = &$this->handlerCalled;

        $this->handler = new class ($response, $called) implements RequestHandlerInterface {
            public function __construct(
                private ResponseInterface $response,
                private bool &$called,
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->called = true;

                return $this->response;
            }
        };
    }

    // -------------------------------------------------------------------------
    // Maintenance mode OFF
    // -------------------------------------------------------------------------

    /**
     * When mode is not set, passes through — handler is called.
     */
    public function testPassesThroughWhenMaintenanceModeOff(): void
    {
        $middleware = new MaintenanceMiddleware([]);
        $middleware->process(new ServerRequest(), $this->handler);
        $this->assertTrue($this->handlerCalled);
    }

    /**
     * When mode=false explicitly, passes through.
     */
    public function testPassesThroughWhenModeExplicitlyFalse(): void
    {
        $middleware = new MaintenanceMiddleware(['mode' => false]);
        $middleware->process(new ServerRequest(), $this->handler);
        $this->assertTrue($this->handlerCalled);
    }

    // -------------------------------------------------------------------------
    // Maintenance mode ON — use subclass to avoid View rendering
    // -------------------------------------------------------------------------

    /**
     * Creates a testable subclass that overrides build() to avoid View rendering.
     *
     * @param array<string, mixed> $config
     */
    private function makeTestableMiddleware(array $config): MaintenanceMiddleware
    {
        return new class ($config) extends MaintenanceMiddleware {
            protected function build(): Response
            {
                return (new Response())->withStatus(503);
            }
        };
    }

    /**
     * When mode=true, handler is NOT called — request is blocked.
     */
    public function testBlocksRequestWhenModeOn(): void
    {
        $middleware = $this->makeTestableMiddleware(['mode' => true]);
        $response = $middleware->process(new ServerRequest(), $this->handler);

        $this->assertFalse($this->handlerCalled);
        $this->assertSame(503, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // isIpAllowed — exact IP
    // -------------------------------------------------------------------------

    /**
     * Allowed IP in /32 CIDR passes through — handler is called.
     */
    public function testAllowedExactIpPassesThrough(): void
    {
        $middleware = $this->makeTestableMiddleware([
            'mode' => true,
            'allowedIps' => ['192.168.1.1/32'],
        ]);

        $request = new ServerRequest(['environment' => ['REMOTE_ADDR' => '192.168.1.1']]);
        $middleware->process($request, $this->handler);

        $this->assertTrue($this->handlerCalled);
    }

    /**
     * Non-allowed IP is blocked — handler is NOT called.
     */
    public function testNonAllowedIpIsBlocked(): void
    {
        $middleware = $this->makeTestableMiddleware([
            'mode' => true,
            'allowedIps' => ['192.168.1.1/32'],
        ]);

        $request = new ServerRequest(['environment' => ['REMOTE_ADDR' => '192.168.1.2']]);
        $response = $middleware->process($request, $this->handler);

        $this->assertFalse($this->handlerCalled);
        $this->assertSame(503, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // isIpAllowed — CIDR ranges
    // -------------------------------------------------------------------------

    /**
     * IP within /24 range passes through.
     */
    public function testIpWithinCidrRangePassesThrough(): void
    {
        $middleware = $this->makeTestableMiddleware([
            'mode' => true,
            'allowedIps' => ['192.168.1.0/24'],
        ]);

        $request = new ServerRequest(['environment' => ['REMOTE_ADDR' => '192.168.1.100']]);
        $middleware->process($request, $this->handler);

        $this->assertTrue($this->handlerCalled);
    }

    /**
     * IP outside /24 range is blocked.
     */
    public function testIpOutsideCidrRangeIsBlocked(): void
    {
        $middleware = $this->makeTestableMiddleware([
            'mode' => true,
            'allowedIps' => ['192.168.1.0/24'],
        ]);

        $request = new ServerRequest(['environment' => ['REMOTE_ADDR' => '192.168.2.1']]);
        $response = $middleware->process($request, $this->handler);

        $this->assertFalse($this->handlerCalled);
        $this->assertSame(503, $response->getStatusCode());
    }

    /**
     * IP within a narrow /30 range passes through.
     */
    public function testIpWithinNarrowCidrRange(): void
    {
        $middleware = $this->makeTestableMiddleware([
            'mode' => true,
            'allowedIps' => ['10.0.0.0/30'],
        ]);

        $request = new ServerRequest(['environment' => ['REMOTE_ADDR' => '10.0.0.2']]);
        $middleware->process($request, $this->handler);

        $this->assertTrue($this->handlerCalled);
    }

    /**
     * Empty allowedIps list blocks everyone.
     */
    public function testEmptyAllowedIpsBlocksAll(): void
    {
        $middleware = $this->makeTestableMiddleware([
            'mode' => true,
            'allowedIps' => [],
        ]);

        $request = new ServerRequest(['environment' => ['REMOTE_ADDR' => '127.0.0.1']]);
        $middleware->process($request, $this->handler);

        $this->assertFalse($this->handlerCalled);
    }

    // -------------------------------------------------------------------------
    // allowedIps as string
    // -------------------------------------------------------------------------

    /**
     * allowedIps as comma-separated string is parsed correctly.
     */
    public function testAllowedIpsAsCommaSeparatedString(): void
    {
        $middleware = $this->makeTestableMiddleware([
            'mode' => true,
            'allowedIps' => '10.0.0.1/32, 10.0.0.2/32',
        ]);

        $allowed = new ServerRequest(['environment' => ['REMOTE_ADDR' => '10.0.0.1']]);
        $middleware->process($allowed, $this->handler);
        $this->assertTrue($this->handlerCalled);

        $this->handlerCalled = false;
        $blocked = new ServerRequest(['environment' => ['REMOTE_ADDR' => '10.0.0.3']]);
        $response = $middleware->process($blocked, $this->handler);
        $this->assertFalse($this->handlerCalled);
        $this->assertSame(503, $response->getStatusCode());
    }

    /**
     * allowedIps as semicolon-separated string is parsed correctly.
     */
    public function testAllowedIpsAsSemicolonSeparatedString(): void
    {
        $middleware = $this->makeTestableMiddleware([
            'mode' => true,
            'allowedIps' => '10.0.0.1/32;10.0.0.2/32',
        ]);

        $request = new ServerRequest(['environment' => ['REMOTE_ADDR' => '10.0.0.2']]);
        $middleware->process($request, $this->handler);

        $this->assertTrue($this->handlerCalled);
    }

    /**
     * IP without /mask automatically gets /32 appended — so it passes through.
     */
    public function testIpWithoutMaskGets32Appended(): void
    {
        $middleware = $this->makeTestableMiddleware([
            'mode' => true,
            'allowedIps' => ['192.168.1.1'], // no /mask — gets /32 appended
        ]);

        $request = new ServerRequest(['environment' => ['REMOTE_ADDR' => '192.168.1.1']]);
        $middleware->process($request, $this->handler);

        $this->assertTrue($this->handlerCalled, 'IP without mask should get /32 and be allowed');
    }
}
