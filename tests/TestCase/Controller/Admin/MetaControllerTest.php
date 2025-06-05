<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\MetaController Test Case
 *
 * @uses \App\Controller\Admin\MetaController
 */
class MetaControllerTest extends TestCase
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
        'app.Meta',
        'app.Plugins',
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
     * @uses \App\Controller\Admin\MetaController::index()
     */
    public function testIndex(): void
    {
        $this->get('/admin/meta');

        $this->assertResponseOk();
        $this->assertNotNull($this->viewVariable('meta'));
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\Admin\MetaController::add()
     */
    public function testAddGet(): void
    {
        $this->get('/admin/meta/add');
        $this->assertResponseOk();
    }

    /**
     * Test add post method
     *
     * @return void
     * @uses \App\Controller\Admin\MetaController::add()
     */
    public function testAddPost(): void
    {
        $postData = [
            'plugin_id' => 1,
            'title' => 'Title1',
            'description' => 'Description1',
            'seo_title' => 'Seo title1',
            'seo_description' => 'Seo description1',
            'seo_keywords' => 'Seo keywords',
        ];
        $this->post('/admin/meta/add', $postData);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/meta');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\Admin\MetaController::edit()
     */
    public function testEditGet(): void
    {
        $this->get('/admin/meta/edit/1');
        $this->assertResponseOk();
    }

    /**
     * Test edit post method
     *
     * @return void
     * @uses \App\Controller\Admin\MetaController::edit()
     */
    public function testEditPost(): void
    {
        $postData = [
            'plugin_id' => 1,
            'title' => 'New Title1',
            'description' => 'New Description1',
            'seo_title' => 'New Seo title1',
            'seo_description' => 'New Seo description1',
            'seo_keywords' => 'New Seo keywords',
        ];
        $this->post('/admin/meta/edit/1', $postData);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/meta');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\Admin\MetaController::delete()
     */
    public function testDeletePost(): void
    {
        $this->post('/admin/meta/delete/1');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/meta');
    }
}
