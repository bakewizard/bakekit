<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\MenuLinksController Test Case
 *
 * @uses \App\Controller\Admin\MenuLinksController
 */
class MenuLinksControllerTest extends TestCase
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
        'app.Plugins',
        'app.MenuLinks',
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

        $this->getTableLocator()->clear();
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\Admin\MenuLinksController::add()
     */
    public function testAddGet(): void
    {
        $this->get('/admin/menu-links/add/2');
        $this->assertResponseOk();
    }

    /**
     * Test add post method
     *
     * @return void
     * @uses \App\Controller\Admin\MenuLinksController::add()
     */
    public function testAddPost(): void
    {
        $postData = [
            'title' => 'Test link',
            'link' => '#',
            'target' => '_self',
        ];
        $this->post('/admin/menu-links/add/2', $postData);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/menus/view/2');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\Admin\MenuLinksController::edit()
     */
    public function testEditGet(): void
    {
        $this->get('/admin/menu-links/edit/4');
        $this->assertResponseOk();
    }

    /**
     * Test edit post method
     *
     * @return void
     * @uses \App\Controller\Admin\MenuLinksController::edit()
     */
    public function testEditPost(): void
    {
        $postData = [
            'title' => 'New Test link',
        ];
        $this->post('/admin/menu-links/edit/4', $postData);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/menus/view/2');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\Admin\MenuLinksController::delete()
     */
    public function testDeletePost(): void
    {
        $this->post('/admin/menu-links/delete/4');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/menus/view/2');
    }
}
