<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\User;
use App\Policy\RequestPolicy;
use Authorization\Policy\Result;
use Cake\Cache\Cache;
use Cake\Cache\Engine\ArrayEngine;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * App\Policy\RequestPolicy Test Case
 *
 * RequestPolicy is the main entry point for HTTP request authorization.
 *
 * before():
 *   - anonymous (null identity) -> true (handled by AuthenticationMiddleware)
 *   - Root (role_id=1)          -> true (bypasses all checks)
 *   - others                    -> null (continues to canAccess)
 *
 * canAccess():
 *   - builds path: Site/{Plugin}/{Controller}/{action}
 *   - delegates to PermissionsTable::check()
 *
 * @uses \App\Policy\RequestPolicy
 */
class RequestPolicyTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Permissions',
        'app.Resources',
        'app.Roles',
        'app.Users',
    ];

    protected RequestPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new RequestPolicy();

        if (!Cache::getConfig('permissions')) {
            Cache::setConfig('permissions', ['className' => ArrayEngine::class]);
        }

        Cache::clear('permissions');
    }

    protected function tearDown(): void
    {
        Cache::clear('permissions');
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // before()
    // -------------------------------------------------------------------------

    /**
     * Anonymous request (identity=null) should return true from before().
     * Redirecting guests is handled by AuthenticationMiddleware upstream.
     */
    public function testBeforeAllowsAnonymous(): void
    {
        $request = new ServerRequest();
        $result = $this->policy->before(null, $request, 'canAccess');
        $this->assertTrue($result);
    }

    /**
     * Root (role_id=1) should get true from before(), bypassing all checks.
     */
    public function testBeforeAllowsRoot(): void
    {
        $user = $this->makeUser(1, 1);
        $request = new ServerRequest();
        $result = $this->policy->before($user, $request, 'canAccess');
        $this->assertTrue($result);
    }

    /**
     * A regular user (role_id != 1) is not Root,
     * so before() should return null to pass control to canAccess().
     */
    public function testBeforeReturnsNullForNonRoot(): void
    {
        $user = $this->makeUser(2, 2);
        $request = new ServerRequest();
        $result = $this->policy->before($user, $request, 'canAccess');
        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // canAccess() — path building
    // -------------------------------------------------------------------------

    /**
     * A request without a plugin should build path Site/System/{Controller}/{action}.
     * Admin inherits Root's allow on Site, so the result should be true.
     */
    public function testCanAccessBuildsPathWithoutPlugin(): void
    {
        $user = $this->makeUser(2, 2); // Admin, not Root
        $request = (new ServerRequest())->withAttribute('params', [
            'plugin' => null,
            'controller' => 'Dashboard',
            'action' => 'index',
            'prefix' => 'Admin',
        ]);

        $result = $this->policy->canAccess($user, $request);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->getStatus());
    }

    /**
     * A request with a plugin should build path Site/{Plugin}/{Controller}/{action}.
     */
    public function testCanAccessBuildsPathWithPlugin(): void
    {
        $user = $this->makeUser(2, 2);
        $request = (new ServerRequest())->withAttribute('params', [
            'plugin' => 'Blogger',
            'controller' => 'Posts',
            'action' => 'index',
            'prefix' => 'Admin',
        ]);

        // Blogger resources are not in fixtures, so check() returns true by default
        $result = $this->policy->canAccess($user, $request);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->getStatus());
    }

    /**
     * When PermissionsTable::check() returns false, canAccess() should return Result(false).
     */
    public function testCanAccessReturnsFalseWhenDenied(): void
    {
        $user = $this->makeUser(2, 2); // Admin (role_id=2)

        $permissions = $this->fetchTable('Permissions');
        $permissions->deny(2, 6); // deny Admin access to Blocks/delete (resource_id=6)
        Cache::clear('permissions');

        $request = (new ServerRequest())->withAttribute('params', [
            'plugin' => null,
            'controller' => 'Blocks',
            'action' => 'delete',
            'prefix' => 'Admin',
        ]);

        $result = $this->policy->canAccess($user, $request);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->getStatus());
    }

    /**
     * The denial message should contain the user's name.
     */
    public function testCanAccessErrorMessageContainsUserName(): void
    {
        $user = $this->makeUser(2, 2);
        $user->first_name = 'Test';
        $user->last_name = 'User';

        $permissions = $this->fetchTable('Permissions');
        $permissions->deny(2, 6);
        Cache::clear('permissions');

        $request = (new ServerRequest())->withAttribute('params', [
            'plugin' => null,
            'controller' => 'Blocks',
            'action' => 'delete',
            'prefix' => 'Admin',
        ]);

        $result = $this->policy->canAccess($user, $request);
        $this->assertFalse($result->getStatus());
        $this->assertStringContainsString('Test', $result->getReason());
    }

    /**
     * A plugin name with slashes (e.g. 'my-plugin/sub') should be correctly
     * camelized and converted to a namespace separator without throwing errors.
     */
    public function testCanAccessHandlesPluginWithNamespace(): void
    {
        $user = $this->makeUser(2, 2);
        $request = (new ServerRequest())->withAttribute('params', [
            'plugin' => 'my-plugin/sub',
            'controller' => 'Posts',
            'action' => 'index',
            'prefix' => 'Admin',
        ]);

        // Resource does not exist in fixtures, so check() returns true
        $result = $this->policy->canAccess($user, $request);
        $this->assertInstanceOf(Result::class, $result);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUser(int $userId, int $roleId): User
    {
        $user = new User(['id' => $userId, 'role_id' => $roleId]);
        $user->clean();

        return $user;
    }
}
