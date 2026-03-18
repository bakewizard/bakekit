<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\Cache\Cache;
use Cake\Cache\Engine\ArrayEngine;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\RolesController Test Case
 *
 * Focuses on editPermissions() — the most complex action:
 *   - valid values (allow/deny/inherit) call the corresponding method
 *   - invalid values are silently ignored
 *   - empty submission does not cause errors
 *
 * @uses \App\Controller\Admin\RolesController
 */
class RolesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Permissions',
        'app.Resources',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (!Cache::getConfig('permissions')) {
            Cache::setConfig('permissions', ['className' => ArrayEngine::class]);
        }

        Cache::clear('permissions');

        // Log in as Root (id=1, role_id=1) — bypasses all authorization checks
        $usersTable = $this->fetchTable('Users');
        $rootUser = $usersTable->get(1, contain: ['Roles']);
        $this->session(['Auth' => ['User' => $rootUser]]);
    }

    protected function tearDown(): void
    {
        Cache::clear('permissions');
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // editPermissions()
    // -------------------------------------------------------------------------

    /**
     * GET editPermissions should render the form with permissions data.
     */
    public function testEditPermissionsGet(): void
    {
        $this->get('/admin/roles/edit-permissions/1');

        $this->assertResponseOk();
        $this->assertNotNull($this->viewVariable('role'));
        $this->assertNotNull($this->viewVariable('resources'));
    }

    /**
     * POST with valid 'allow' value should save allowed=true for the given role/resource.
     */
    public function testEditPermissionsAllow(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // perms[resource_id][role_id] = 'allow'
        $this->post('/admin/roles/edit-permissions/2', [
            'perms' => ['12' => ['2' => 'allow']],
        ]);

        $this->assertResponseSuccess();

        $perm = $this->fetchTable('Permissions')
            ->find()
            ->where(['role_id' => 2, 'resource_id' => 12])
            ->first();

        $this->assertNotNull($perm);
        $this->assertTrue($perm->allowed);
    }

    /**
     * POST with valid 'deny' value should save allowed=false for the given role/resource.
     */
    public function testEditPermissionsDeny(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/roles/edit-permissions/2', [
            'perms' => ['12' => ['2' => 'deny']],
        ]);

        $this->assertResponseSuccess();

        $perm = $this->fetchTable('Permissions')
            ->find()
            ->where(['role_id' => 2, 'resource_id' => 12])
            ->first();

        $this->assertNotNull($perm);
        $this->assertFalse($perm->allowed);
    }

    /**
     * POST with valid 'inherit' value should delete the permission record.
     */
    public function testEditPermissionsInherit(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // First create a record to inherit from
        $this->fetchTable('Permissions')->allow(2, 12);

        $this->post('/admin/roles/edit-permissions/2', [
            'perms' => ['12' => ['2' => 'inherit']],
        ]);

        $this->assertResponseSuccess();

        $perm = $this->fetchTable('Permissions')
            ->find()
            ->where(['role_id' => 2, 'resource_id' => 12])
            ->first();

        $this->assertNull($perm, 'inherit should delete the permission record');
    }

    /**
     * POST with an invalid value should be silently ignored — no record saved.
     */
    public function testEditPermissionsIgnoresInvalidValue(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/roles/edit-permissions/2', [
            'perms' => ['12' => ['2' => 'deleteAll']],
        ]);

        $this->assertResponseSuccess();

        $perm = $this->fetchTable('Permissions')
            ->find()
            ->where(['role_id' => 2, 'resource_id' => 12])
            ->first();

        $this->assertNull($perm, 'Invalid permission value should be ignored');
    }

    /**
     * POST with empty permissions should not cause errors.
     */
    public function testEditPermissionsEmptySubmission(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/roles/edit-permissions/2', []);

        $this->assertResponseSuccess();
    }

    // -------------------------------------------------------------------------
    // resetPermissions()
    // -------------------------------------------------------------------------

    /**
     * resetPermissions() should delete all permission records for the given role
     * and clear the permissions cache.
     */
    public function testResetPermissions(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Give Admin some permissions first
        $permissions = $this->fetchTable('Permissions');
        $permissions->allow(2, 12);
        $permissions->allow(2, 11);

        $this->post('/admin/roles/reset-permissions/2');

        $this->assertRedirectContains('/admin/roles/edit-permissions/2');

        $count = $permissions->find()->where(['role_id' => 2])->count();
        $this->assertSame(0, $count, 'All permissions for the role should be deleted');
    }
}
