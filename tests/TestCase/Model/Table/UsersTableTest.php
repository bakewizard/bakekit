<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\UsersTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\UsersTable Test Case
 *
 * Covers the access-control-relevant validation:
 *   - role_id cannot be set to 1 (only one Root allowed)
 *   - email and alias must be unique
 *
 * @uses \App\Model\Table\UsersTable
 */
class UsersTableTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'app.Roles',
    ];

    protected UsersTable $Users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Users = $this->fetchTable('Users');
    }

    protected function tearDown(): void
    {
        unset($this->Users);
        parent::tearDown();
    }

    /**
     * Creating a new user with role_id=1 should fail validation.
     */
    public function testCannotCreateUserWithRootRole(): void
    {
        $user = $this->Users->newEntity([
            'first_name' => 'Hacker',
            'last_name' => 'McHack',
            'alias' => 'hacker',
            'email' => 'hacker@example.com',
            'password' => 'password123',
            'role_id' => 1,
        ]);

        $this->assertFalse($this->Users->save($user));
        $this->assertArrayHasKey('role_id', $user->getErrors());
    }

    /**
     * Creating a new user with a non-Root role should succeed.
     */
    public function testCanCreateUserWithNonRootRole(): void
    {
        $user = $this->Users->newEntity([
            'first_name' => 'Regular',
            'last_name' => 'User',
            'alias' => 'regular',
            'email' => 'regular@example.com',
            'password' => 'password123',
            'role_id' => 2,
        ]);

        $this->assertNotFalse($this->Users->save($user));
        $this->assertEmpty($user->getErrors());
    }

    /**
     * Editing an existing user without changing role_id should succeed.
     * The role_id field is not present in the patch data, so validation is not triggered.
     */
    public function testCanEditUserWithoutChangingRole(): void
    {
        $user = $this->Users->get(1);
        $user = $this->Users->patchEntity($user, ['first_name' => 'Updated']);

        $this->assertNotFalse($this->Users->save($user));
        $this->assertEmpty($user->getErrors());
    }

    /**
     * Email must be unique — saving a duplicate should fail.
     */
    public function testEmailMustBeUnique(): void
    {
        $user = $this->Users->newEntity([
            'first_name' => 'Dupe',
            'last_name' => 'User',
            'alias' => 'dupe-user',
            'email' => 'neo@matrix.net', // already exists in fixture
            'password' => 'password123',
            'role_id' => 2,
        ]);

        $this->assertFalse($this->Users->save($user));
        $this->assertArrayHasKey('email', $user->getErrors());
    }

    /**
     * Alias must be unique — saving a duplicate should fail.
     */
    public function testAliasMustBeUnique(): void
    {
        $user = $this->Users->newEntity([
            'first_name' => 'Dupe',
            'last_name' => 'User',
            'alias' => 'root', // already exists in fixture
            'email' => 'unique@example.com',
            'password' => 'password123',
            'role_id' => 2,
        ]);

        $this->assertFalse($this->Users->save($user));
        $this->assertArrayHasKey('alias', $user->getErrors());
    }
}
