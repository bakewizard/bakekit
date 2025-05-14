<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\RegionsController Test Case
 *
 * @uses \App\Controller\Admin\RegionsController
 */
class RegionsControllerTest extends TestCase
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
        'app.Regions',
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
     * @uses \App\Controller\Admin\RegionsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/admin/regions');

        $this->assertResponseOk();
        $this->assertResponseContains('Regions'); // Check for a heading or title
        $this->assertNotNull($this->viewVariable('regions')); // Check if the 'users' paginated set is passed
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\Admin\RegionsController::view()
     */
    public function testView(): void
    {
        $this->get('/admin/regions/view/1');
        $this->assertResponseOk();

        $this->assertNotEmpty($this->viewVariable('region'));
        $this->assertEquals(1, $this->viewVariable('region')->id);
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\Admin\RegionsController::add()
     */
    public function testAddGet(): void
    {
        $this->get('/admin/regions/add');
        $this->assertResponseOk();
    }

    /**
     * Test add post method
     *
     * @return void
     * @uses \App\Controller\Admin\RegionsController::add()
     */
    public function testAddPost(): void
    {
        $postData = [
            'alias' => 'test-region',
            'description' => 'This is a test region.',
        ];
        $this->post('/admin/regions/add', $postData);
        $this->assertResponseCode(302); // redirect
        $this->assertRedirectContains('/admin/regions');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\Admin\RegionsController::edit()
     */
    public function testEditGet(): void
    {
        $this->get('/admin/regions/edit/1');
        $this->assertResponseOk();
    }

    /**
     * Test edit post method
     *
     * @return void
     * @uses \App\Controller\Admin\RegionsController::edit()
     */
    public function testEditPost(): void
    {
        $postData = [
            'alias' => 'edited-region',
            'description' => 'This is an edited region.',
        ];
        $this->post('/admin/regions/edit/1', $postData);
        $this->assertResponseCode(302); // redirect
        $this->assertRedirectContains('/admin/regions');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\Admin\RegionsController::delete()
     */
    public function testDeletePost(): void
    {
        $this->post('/admin/regions/delete/1');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/regions');
    }
}
