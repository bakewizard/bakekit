<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PermissionsTable;
use Cake\Cache\Cache;
use Cake\Cache\Engine\ArrayEngine;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PermissionsTable Test Case
 *
 * Covers the core access control logic:
 * - PermissionsTable::check() — main authorization check
 * - PermissionsTable::getPermissions() — builds the permission map for a role
 * - PermissionsTable::allow() / deny() / inherit()
 *
 * Role hierarchy in fixtures:
 *   Root (id=1) → Admin (id=2) → User (id=3) → Authenticated (id=4)
 *
 * Resource hierarchy (Site/System/Controller/action):
 *   Site (id=1) → System (id=2) → Blocks (id=27) → add (id=28), delete (id=30)
 *                               → Dashboard (id=32) → index (id=33)
 *
 * @uses \App\Model\Table\PermissionsTable
 */
class PermissionsTableTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Permissions',
        'app.Resources',
        'app.Roles',
    ];

    protected PermissionsTable $Permissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Permissions = $this->fetchTable('Permissions');

        // Register an in-memory cache for tests if not already configured.
        // Application::bootstrap() normally does this, but it's not called in unit tests.
        if (!Cache::getConfig('permissions')) {
            Cache::setConfig('permissions', ['className' => ArrayEngine::class]);
        }

        Cache::clear('permissions');
    }

    protected function tearDown(): void
    {
        unset($this->Permissions);
        Cache::clear('permissions');
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // check()
    // -------------------------------------------------------------------------

    /**
     * When no permission record exists for a role, check() should return true.
     *
     * The system is "open by default": if nothing is explicitly denied,
     * access is granted. check() returns true for unknown resource paths.
     */
    public function testCheckReturnsTrueWhenNoPermissionSet(): void
    {
        // Role 4 (Authenticated) has no permission records at all
        $result = $this->Permissions->check(4, 'Site/System/Dashboard/index');
        $this->assertTrue($result, 'Unknown resource path should be allowed by default');
    }

    /**
     * Root (id=1) has an explicit allow on Site (resource_id=1).
     * All child resources should inherit that allow.
     */
    public function testCheckReturnsTrueWhenRoleHasExplicitAllow(): void
    {
        $result = $this->Permissions->check(1, 'Site/System/Dashboard/index');
        $this->assertTrue($result);
    }

    /**
     * When a role is explicitly denied a resource, check() should return false.
     */
    public function testCheckReturnsFalseWhenDenied(): void
    {
        $this->Permissions->deny(2, 30); // deny Admin access to Blocks/delete (id=30)

        $result = $this->Permissions->check(2, 'Site/System/Blocks/delete');
        $this->assertFalse($result);
    }

    /**
     * A child role inherits a deny from its parent role.
     * Admin (id=2) denies -> User (id=3) should also be denied.
     */
    public function testCheckInheritsParentDeny(): void
    {
        $this->Permissions->deny(2, 30); // Admin denies Blocks/delete (id=30)

        $result = $this->Permissions->check(3, 'Site/System/Blocks/delete');
        $this->assertFalse($result, 'Child role should inherit deny from parent');
    }

    /**
     * A child role can override a parent allow by explicitly denying.
     */
    public function testCheckChildCanOverrideParentAllow(): void
    {
        // Root already allows everything via fixture
        $this->Permissions->deny(3, 33); // User explicitly denies Dashboard/index (id=33)

        $result = $this->Permissions->check(3, 'Site/System/Dashboard/index');
        $this->assertFalse($result, 'Child role can deny what parent allowed');
    }

    /**
     * A child role can override a parent deny by explicitly allowing.
     */
    public function testCheckChildCanOverrideParentDeny(): void
    {
        $this->Permissions->deny(2, 28); // Admin denies Blocks/add (id=28)
        $this->Permissions->allow(3, 28); // User explicitly allows Blocks/add (id=28)

        $result = $this->Permissions->check(3, 'Site/System/Blocks/add');
        $this->assertTrue($result, 'Child role can allow what parent denied');
    }

    // -------------------------------------------------------------------------
    // allow() / deny() / inherit()
    // -------------------------------------------------------------------------

    /**
     * allow() should save a record with allowed=true.
     * Calling it on an existing record should update it (upsert behaviour).
     */
    public function testAllow(): void
    {
        $result = $this->Permissions->allow(2, 33); // Dashboard/index (id=33)
        $this->assertTrue($result);

        $perm = $this->Permissions->find()
            ->where(['role_id' => 2, 'resource_id' => 33])
            ->first();
        $this->assertNotNull($perm);
        $this->assertTrue($perm->allowed);
    }

    /**
     * deny() should save a record with allowed=false.
     */
    public function testDeny(): void
    {
        $result = $this->Permissions->deny(2, 33); // Dashboard/index (id=33)
        $this->assertTrue($result);

        $perm = $this->Permissions->find()
            ->where(['role_id' => 2, 'resource_id' => 33])
            ->first();
        $this->assertNotNull($perm);
        $this->assertFalse($perm->allowed);
    }

    /**
     * inherit() should delete the permission record,
     * causing the role to fall back to inheriting from its parent.
     */
    public function testInherit(): void
    {
        $this->Permissions->allow(2, 33); // Dashboard/index (id=33)
        $this->assertNotNull(
            $this->Permissions->find()->where(['role_id' => 2, 'resource_id' => 33])->first(),
        );

        $result = $this->Permissions->inherit(2, 33);
        $this->assertTrue($result);

        $perm = $this->Permissions->find()
            ->where(['role_id' => 2, 'resource_id' => 33])
            ->first();
        $this->assertNull($perm, 'inherit() should delete the permission record');
    }

    // -------------------------------------------------------------------------
    // getPermissions() — structure and caching
    // -------------------------------------------------------------------------

    /**
     * getPermissions() should return an associative array
     * keyed by resource path (e.g. 'Site/System/Dashboard/index').
     */
    public function testGetPermissionsReturnsPathIndexedArray(): void
    {
        $perms = $this->Permissions->getPermissions(1);

        $this->assertIsArray($perms);
        $this->assertArrayHasKey('Site', $perms, 'Root node Site must be present');
        $this->assertArrayHasKey('Site/System', $perms);
        $this->assertArrayHasKey('Site/System/Dashboard', $perms);
        $this->assertArrayHasKey('Site/System/Dashboard/index', $perms);
    }

    /**
     * Each entry in getPermissions() should contain a permissions array
     * with 'allowed' and 'inherited' keys.
     */
    public function testGetPermissionsEntryStructure(): void
    {
        $perms = $this->Permissions->getPermissions(1);

        foreach ($perms as $path => $entry) {
            $this->assertArrayHasKey('id', $entry, "Entry '$path' must have 'id'");
            $this->assertArrayHasKey('alias', $entry, "Entry '$path' must have 'alias'");
            $this->assertArrayHasKey('label', $entry, "Entry '$path' must have 'label'");
            $this->assertArrayHasKey('permissions', $entry, "Entry '$path' must have 'permissions'");
            $this->assertArrayHasKey('allowed', $entry['permissions'], "permissions[$path] must have 'allowed'");
            $this->assertArrayHasKey('inherited', $entry['permissions'], "permissions[$path] must have 'inherited'");
        }
    }

    /**
     * Root's own explicit allow on Site should not be marked as inherited.
     */
    public function testGetPermissionsRootNotInherited(): void
    {
        $perms = $this->Permissions->getPermissions(1);

        $this->assertFalse(
            $perms['Site']['permissions']['inherited'],
            'An explicitly set permission should not be marked as inherited',
        );
    }

    /**
     * A child role with no own records should mark permissions as inherited.
     */
    public function testGetPermissionsChildRoleInherits(): void
    {
        $perms = $this->Permissions->getPermissions(2); // Admin

        // Admin has no own record on Site, so it inherits from Root
        $this->assertTrue($perms['Site']['permissions']['inherited'], 'Admin inherits Site from Root (inherited=true)');
    }

    /**
     * The result of getPermissions() should be cached.
     * Two consecutive calls without any data changes should return the same result.
     */
    public function testGetPermissionsIsCached(): void
    {
        $perms1 = $this->Permissions->getPermissions(1);
        $perms2 = $this->Permissions->getPermissions(1);

        $this->assertSame($perms1, $perms2, 'getPermissions() should return cached result');
    }

    /**
     * After allow()/deny()/inherit(), getPermissions() should reflect updated data.
     * Cache is cleared automatically via afterSave/afterDelete callbacks.
     */
    public function testGetPermissionsRefreshesAfterCacheClear(): void
    {
        $perms1 = $this->Permissions->getPermissions(2);
        $this->Permissions->deny(2, 33); // afterSave clears cache automatically
        $perms2 = $this->Permissions->getPermissions(2);

        $this->assertNotSame(
            $perms1['Site/System/Dashboard/index']['permissions']['allowed'],
            $perms2['Site/System/Dashboard/index']['permissions']['allowed'],
            'getPermissions() should return fresh data after a permission change',
        );
    }
}
