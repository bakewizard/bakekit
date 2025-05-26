<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\UsersController Test Case
 *
 * @uses \App\Controller\Admin\UsersController
 */
class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Roles',
    ];

    private $adminUser; // To store admin user data from fixture

    /**
     * setUp method
     *
     * This method is called before each test method.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $usersTable = $this->fetchTable('Users');
        // Load a user from the fixture to use for authenticated tests
        // Ensure your UsersFixture has a user with role_id that corresponds to an admin role in RolesFixture
        // For this example, let's assume user with ID 1 is an admin.
        $this->adminUser = $usersTable->get(1, contain: ['Roles']); // Ensure user ID 1 exists in UsersFixture
    }

    /**
     * tearDown method
     *
     * This method is called after each test method.
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Users);
        parent::tearDown();
    }

    /**
     * Helper method to log in as the admin user.
     * This directly sets the session, bypassing the login form.
     */
    protected function loginAsAdmin(): void
    {
        $this->session([
            'Auth' => [
                'User' => $this->adminUser,
            ],
        ]);
    }

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\Admin\UsersController::index()
     */
    public function testIndex(): void
    {
        $this->loginAsAdmin();
        $this->get('/admin/users');

        $this->assertResponseOk();
        $this->assertResponseContains('Users'); // Check for a heading or title
        $this->assertNotNull($this->viewVariable('users')); // Check if the 'users' paginated set is passed
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\Admin\UsersController::add()
     */
    public function testAdd(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'first_name' => 'Test',
            'last_name' => 'Test',
            'alias' => 'test',
            'email' => 'test@test.net',
            'password' => 'password123',
            'role_id' => 2,
        ];

        $this->post('/admin/users/add', $data);
        $this->assertResponseSuccess();

        $this->assertRedirectContains('/admin/users');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\Admin\UsersController::edit()
     */
    public function testEdit(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'first_name' => 'editeduser',
        ];

        $this->post('/admin/users/edit/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirectContains('/admin/users');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\Admin\UsersController::delete()
     */
    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/users/delete/2');
        $this->assertRedirectContains('/admin/users');
    }

    /**
     * Test login success method
     *
     * @return void
     * @uses \App\Controller\Admin\UsersController::login()
     */
    public function testLoginSuccess(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/users/login', [
            'email' => 'neo@matrix.net',
            'password' => 'matrix',
        ]);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin');

        $this->assertSession(1, 'Auth.User.id');
    }

    /**
     * Test login failure method
     *
     * @return void
     * @uses \App\Controller\Admin\UsersController::login()
     */
    public function testLoginFailure(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/users/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('User name or password is incorrect');
        $this->assertSessionNotHasKey('Auth.User');
    }

    /**
     * Test logout method
     *
     * @return void
     * @uses \App\Controller\Admin\UsersController::logout()
     */
    public function testLogout(): void
    {
        $this->get('/admin/users/logout');

        $this->assertResponseCode(302);
        // $this->assertRedirect('/admin/users/login');
        $this->assertSessionNotHasKey('Auth.User');
    }
}
