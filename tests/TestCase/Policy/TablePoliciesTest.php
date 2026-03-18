<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\User;
use App\Policy\RolesTablePolicy;
use App\Policy\UsersTablePolicy;
use Authorization\Policy\Result;
use Cake\TestSuite\TestCase;
use stdClass;

/**
 * RolesTablePolicy and UsersTablePolicy Test Case
 *
 * RolesTablePolicy:
 *   - before() allows anonymous and Root, returns null for others
 *   - canIndex() denies non-Root
 *
 * UsersTablePolicy:
 *   - scopeIndex() returns all users for Root
 *   - scopeIndex() restricts to own record for non-Root
 *
 * @uses \App\Policy\RolesTablePolicy
 * @uses \App\Policy\UsersTablePolicy
 */
class TablePoliciesTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'app.Roles',
    ];

    // =========================================================================
    // RolesTablePolicy
    // =========================================================================

    /**
     * Anonymous identity -> true.
     */
    public function testRolesTablePolicyBeforeAllowsAnonymous(): void
    {
        $policy = new RolesTablePolicy();
        $result = $policy->before(null, new stdClass(), 'canIndex');
        $this->assertTrue($result);
    }

    /**
     * Root -> true, bypasses canIndex.
     */
    public function testRolesTablePolicyBeforeAllowsRoot(): void
    {
        $policy = new RolesTablePolicy();
        $root = $this->makeUser(1, 1);

        $result = $policy->before($root, new stdClass(), 'canIndex');
        $this->assertTrue($result);
    }

    /**
     * Non-Root -> null, passes control to canIndex.
     */
    public function testRolesTablePolicyBeforeReturnsNullForNonRoot(): void
    {
        $policy = new RolesTablePolicy();
        $user = $this->makeUser(2, 2);

        $result = $policy->before($user, new stdClass(), 'canIndex');
        $this->assertNull($result);
    }

    /**
     * canIndex() always denies non-Root users.
     */
    public function testRolesTablePolicyCanIndexDeniesNonRoot(): void
    {
        $policy = new RolesTablePolicy();
        $user = $this->makeUser(2, 2);
        $query = $this->fetchTable('Roles')->find();

        $result = $policy->canIndex($user, $query);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->getStatus());
    }

    // =========================================================================
    // UsersTablePolicy
    // =========================================================================

    /**
     * Root should see all users — no WHERE clause added.
     */
    public function testUsersTablePolicyScopeIndexAllowsRootToSeeAll(): void
    {
        $policy = new UsersTablePolicy();
        $root = $this->makeUser(1, 1);
        $query = $this->fetchTable('Users')->find();

        $result = $policy->scopeIndex($root, $query);

        // Root gets the unmodified query — no id restriction
        $this->assertSame(2, $result->count(), 'Root should see all users');
    }

    /**
     * Non-Root should only see their own record.
     */
    public function testUsersTablePolicyScopeIndexRestrictsNonRoot(): void
    {
        $policy = new UsersTablePolicy();
        $user = $this->makeUser(2, 2);
        $query = $this->fetchTable('Users')->find();

        $result = $policy->scopeIndex($user, $query);

        $users = $result->all()->toList();
        $this->assertCount(1, $users, 'Non-Root should only see their own record');
        $this->assertSame(2, $users[0]->id);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeUser(int $userId, int $roleId): User
    {
        $user = new User(['id' => $userId, 'role_id' => $roleId]);
        $user->clean();

        return $user;
    }
}
