<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\BlocksController Test Case
 *
 * @uses \App\Controller\Admin\BlocksController
 */
class BlocksControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Settings',
        'app.Plugins',
        'app.Regions',
        'app.Blocks',
    ];

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->session([
            'Auth' => [
                'User' => $this->fetchTable('Users')->get(1),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // add()
    // -------------------------------------------------------------------------

    /**
     * add() renders the form for a given region id.
     *
     * @uses \App\Controller\Admin\BlocksController::add()
     */
    public function testAddGet(): void
    {
        $this->get('/admin/blocks/add/1');
        $this->assertResponseOk();
    }

    /**
     * add() saves a new block and redirects to the themes blocks page.
     *
     * @uses \App\Controller\Admin\BlocksController::add()
     */
    public function testAddPost(): void
    {
        $this->post('/admin/blocks/add/1', [
            'region_id' => 1,
            'title' => 'Test Block',
            'description' => 'This is a test block.',
            'cell' => null,
            'template' => null,
            'enabled' => true,
        ]);

        $this->assertRedirect('/admin/themes/blocks');
        $this->assertFlashMessage('The block has been saved.');
    }

    // -------------------------------------------------------------------------
    // edit()
    // -------------------------------------------------------------------------

    /**
     * edit() renders the edit form for an existing block.
     *
     * @uses \App\Controller\Admin\BlocksController::edit()
     */
    public function testEditGet(): void
    {
        $this->get('/admin/blocks/edit/1');
        $this->assertResponseOk();
    }

    /**
     * edit() saves changes and redirects to the themes blocks page.
     *
     * @uses \App\Controller\Admin\BlocksController::edit()
     */
    public function testEditPost(): void
    {
        $this->put('/admin/blocks/edit/1', [
            'title' => 'Updated Title',
        ]);

        $this->assertRedirect('/admin/themes/blocks');
        $this->assertFlashMessage('The block has been saved.');
    }

    // -------------------------------------------------------------------------
    // delete()
    // -------------------------------------------------------------------------

    /**
     * delete() removes the block and redirects to the themes blocks page.
     *
     * @uses \App\Controller\Admin\BlocksController::delete()
     */
    public function testDelete(): void
    {
        $this->post('/admin/blocks/delete/1');

        $this->assertRedirect('/admin/themes/blocks');
        $this->assertFlashMessage('The block has been deleted.');
    }

    // -------------------------------------------------------------------------
    // moveUp() / moveDown()
    // -------------------------------------------------------------------------

    /**
     * moveUp() reorders the block and redirects to the themes blocks page.
     *
     * @uses \App\Controller\Admin\BlocksController::moveUp()
     */
    public function testMoveUp(): void
    {
        $this->post('/admin/blocks/move-up/1');

        $this->assertRedirect('/admin/themes/blocks');
        $this->assertFlashMessage('The Block has been moved Up.');
    }

    /**
     * moveDown() reorders the block and redirects to the themes blocks page.
     *
     * @uses \App\Controller\Admin\BlocksController::moveDown()
     */
    public function testMoveDown(): void
    {
        $this->post('/admin/blocks/move-down/1');

        $this->assertRedirect('/admin/themes/blocks');
        $this->assertFlashMessage('The Block has been moved down.');
    }

    // -------------------------------------------------------------------------
    // config()
    // -------------------------------------------------------------------------

    /**
     * config() renders the cell settings form on GET.
     *
     * @uses \App\Controller\Admin\BlocksController::config()
     */
    public function testConfigGet(): void
    {
        $this->get('/admin/blocks/config/1');
        $this->assertResponseOk();
    }

    /**
     * config() saves valid cell params and redirects to the themes blocks page.
     *
     * @uses \App\Controller\Admin\BlocksController::config()
     */
    public function testConfigPostValid(): void
    {
        $this->post('/admin/blocks/config/1', [
            'menu' => '3',
            'container' => ['class' => 'nav navbar-nav'],
        ]);

        $this->assertRedirect('/admin/themes/blocks');
        $this->assertFlashMessage('Configuration saved');

        $block = $this->fetchTable('Blocks')->get(1);
        $this->assertArrayHasKey('menu', $block->params);
        $this->assertArrayHasKey('container', $block->params);
    }
}
