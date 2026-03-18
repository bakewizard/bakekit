<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Policy\RolePolicy;
use App\Policy\UserPolicy;
use Authorization\Policy\Result;
use Cake\TestSuite\TestCase;

/**
 * App\Policy\UserPolicy and App\Policy\RolePolicy Test Case
 *
 * UserPolicy — restricts operations on user profiles:
 *   - view/edit  — own profile only
 *   - add/delete — Root only
 *   - Root cannot delete itself
 *
 * RolePolicy — restricts operations on roles:
 *   - only Root can perform any action
 *   - Root cannot delete the Root role
 *   - Known bug: before() compares $identity->id == $resource->id (user id vs role id).
 *     This works only because both Root user and Root role have id=1.
 *     The correct check would be: $resource->id === 1 or $resource->isRoot().
 *
 * @uses \App\Policy\UserPolicy
 * @uses \App\Policy\RolePolicy
 */
class EntityPoliciesTest extends TestCase
{
    // =========================================================================
    // UserPolicy
    // =========================================================================

    /**
     * Anonymous identity -> true (AuthenticationMiddleware handles redirect).
     */
    public function testUserPolicyBeforeAllowsAnonymous(): void
    {
        $policy = new UserPolicy();
        $user = new User(['id' => 1, 'role_id' => 2]);
        $result = $policy->before(null, $user, 'canView');
        $this->assertTrue($result);
    }

    /**
     * Root can perform any action except deleting itself.
     */
    public function testUserPolicyBeforeAllowsRoot(): void
    {
        $policy = new UserPolicy();
        $root = $this->makeUser(1, 1);
        $other = $this->makeUser(2, 2);

        $result = $policy->before($root, $other, 'canEdit');
        $this->assertTrue($result);
    }

    /**
     * Root cannot delete its own account.
     */
    public function testUserPolicyBeforePreventsRootSelfDelete(): void
    {
        $policy = new UserPolicy();
        $root = $this->makeUser(1, 1);

        $result = $policy->before($root, $root, 'delete');
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->getStatus());
        $this->assertStringContainsString('Root', $result->getReason());
    }

    /**
     * Root can delete other users.
     */
    public function testUserPolicyBeforeAllowsRootDeleteOther(): void
    {
        $policy = new UserPolicy();
        $root = $this->makeUser(1, 1);
        $other = $this->makeUser(2, 2);

        $result = $policy->before($root, $other, 'delete');
        $this->assertTrue($result);
    }

    /**
     * Regular user -> null, passing control to canView/canEdit/etc.
     */
    public function testUserPolicyBeforeReturnsNullForRegularUser(): void
    {
        $policy = new UserPolicy();
        $user = $this->makeUser(2, 2);
        $resource = $this->makeUser(3, 3);

        $result = $policy->before($user, $resource, 'canView');
        $this->assertNull($result);
    }

    /**
     * canView() allows viewing own profile.
     */
    public function testCanViewAllowsOwnProfile(): void
    {
        $policy = new UserPolicy();
        $user = $this->makeUser(5, 2);

        $result = $policy->canView($user, $user);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->getStatus());
    }

    /**
     * canView() denies viewing another user's profile.
     */
    public function testCanViewDeniesOtherProfile(): void
    {
        $policy = new UserPolicy();
        $user = $this->makeUser(5, 2);
        $other = $this->makeUser(6, 2);

        $result = $policy->canView($user, $other);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->getStatus());
    }

    /**
     * canEdit() allows editing own profile.
     */
    public function testCanEditAllowsOwnProfile(): void
    {
        $policy = new UserPolicy();
        $user = $this->makeUser(5, 2);

        $result = $policy->canEdit($user, $user);
        $this->assertTrue($result->getStatus());
    }

    /**
     * canEdit() denies editing another user's profile.
     */
    public function testCanEditDeniesOtherProfile(): void
    {
        $policy = new UserPolicy();
        $user = $this->makeUser(5, 2);
        $other = $this->makeUser(6, 2);

        $result = $policy->canEdit($user, $other);
        $this->assertFalse($result->getStatus());
    }

    /**
     * canAdd() always denies for non-Root.
     * (Root is intercepted by before() -> true and never reaches canAdd.)
     */
    public function testCanAddDeniesForNonRoot(): void
    {
        $policy = new UserPolicy();
        $user = $this->makeUser(5, 2);

        $result = $policy->canAdd($user, new User());
        $this->assertFalse($result->getStatus());
    }

    /**
     * canDelete() always denies for non-Root.
     */
    public function testCanDeleteDeniesForNonRoot(): void
    {
        $policy = new UserPolicy();
        $user = $this->makeUser(5, 2);
        $target = $this->makeUser(6, 2);

        $result = $policy->canDelete($user, $target);
        $this->assertFalse($result->getStatus());
    }

    // =========================================================================
    // RolePolicy
    // =========================================================================

    /**
     * Anonymous identity -> true.
     */
    public function testRolePolicyBeforeAllowsAnonymous(): void
    {
        $policy = new RolePolicy();
        $role = new Role(['id' => 2]);
        $result = $policy->before(null, $role, 'canEdit');
        $this->assertTrue($result);
    }

    /**
     * Root can perform any action on non-Root roles.
     */
    public function testRolePolicyBeforeAllowsRootForOtherRoles(): void
    {
        $policy = new RolePolicy();
        $root = $this->makeUser(1, 1);
        $role = new Role(['id' => 2]);

        $result = $policy->before($root, $role, 'delete');
        $this->assertTrue($result);
    }

    /**
     * Root cannot delete the Root role.
     *
     * Known bug: before() compares $identity->id == $resource->id (user id vs role id).
     * This works only because Root user id=1 and Root role id=1 happen to be equal.
     * The correct check should be: $resource->id === 1 or $resource->isRoot().
     */
    public function testRolePolicyBeforePreventsRootRoleDeletion(): void
    {
        $policy = new RolePolicy();
        $root = $this->makeUser(1, 1);
        $rootRole = new Role(['id' => 1]);

        $result = $policy->before($root, $rootRole, 'delete');
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->getStatus());
    }

    /**
     * Non-Root user -> null, passing control to can* methods.
     */
    public function testRolePolicyBeforeReturnsNullForNonRoot(): void
    {
        $policy = new RolePolicy();
        $user = $this->makeUser(2, 2);
        $role = new Role(['id' => 3]);

        $result = $policy->before($user, $role, 'canEdit');
        $this->assertNull($result);
    }

    /**
     * __call() magic fallback denies any undefined action for non-Root.
     */
    public function testRolePolicyFallbackDeniesEverything(): void
    {
        $policy = new RolePolicy();
        $result = $policy->canEdit(new User(), new Role());
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->getStatus());
    }

    // =========================================================================
    // Entity helpers
    // =========================================================================

    /**
     * User::isRoot() should return true only when role_id === 1.
     */
    public function testUserIsRoot(): void
    {
        $this->assertTrue($this->makeUser(1, 1)->isRoot(), 'role_id=1 -> isRoot()=true');
        $this->assertFalse($this->makeUser(2, 2)->isRoot(), 'role_id=2 -> isRoot()=false');
    }

    /**
     * Role::isRoot() should return true only when id === 1.
     */
    public function testRoleIsRoot(): void
    {
        $this->assertTrue((new Role(['id' => 1]))->isRoot(), 'Role id=1 -> isRoot()=true');
        $this->assertFalse((new Role(['id' => 2]))->isRoot(), 'Role id=2 -> isRoot()=false');
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
