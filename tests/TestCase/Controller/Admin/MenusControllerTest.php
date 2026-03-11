<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\MenusController Test Case
 *
 * @uses \App\Controller\Admin\MenusController
 */
class MenusControllerTest extends TestCase
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
        'app.Menus',
    ];

    /**
     * setUp method
     *
     * This method is called before each test method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $user = $this->fetchTable('Users')->get(1);

        $this->session([
            'Auth' => [
                'User' => $user,
            ],
        ]);
    }

    /**
     * tearDown method
     *
     * This method is called after each test method.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\Admin\MenusController::index()
     */
    public function testIndex(): void
    {
        $this->get('/admin/menus');

        $this->assertResponseOk();
        $this->assertNotNull($this->viewVariable('menus'));
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\Admin\MenusController::view()
     */
    public function testView(): void
    {
        $this->get('/admin/menus/view/1');
        $this->assertResponseOk();

        $this->assertNotEmpty($this->viewVariable('menu'));
        $this->assertEquals(1, $this->viewVariable('menu')->id);
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\Admin\MenusController::add()
     */
    public function testAddGet(): void
    {
        $this->get('/admin/menus/add');
        $this->assertResponseOk();
    }

    /**
     * Test add post method
     *
     * @return void
     * @uses \App\Controller\Admin\MenusController::add()
     */
    public function testAddPost(): void
    {
        $postData = [
            'name' => 'New Test Menu',
            'description' => 'A test menu',
        ];
        $this->post('/admin/menus/add', $postData);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/menus');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\Admin\MenusController::edit()
     */
    public function testEditGet(): void
    {
        $this->get('/admin/menus/edit/1');
        $this->assertResponseOk();
    }

    /**
     * Test edit post method
     *
     * @return void
     * @uses \App\Controller\Admin\MenusController::edit()
     */
    public function testEditPost(): void
    {
        $postData = [
            'name' => 'Updated Menu',
            'description' => 'Updated description',
        ];
        $this->post('/admin/menus/edit/1', $postData);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/menus');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\Admin\MenusController::delete()
     */
    public function testDeletePost(): void
    {
        $this->post('/admin/menus/delete/1');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/menus');
    }
}
