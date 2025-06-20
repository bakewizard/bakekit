<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class BlocksControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Plugins',
        'app.Regions',
        'app.Blocks',
    ];

    public function setUp(): void
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

    public function testAdd(): void
    {
        $this->get('/admin/blocks/add/4');
        $this->assertResponseOk();

        $data = [
            'region_id' => 4,
            'alias' => 'test-block',
            'title' => 'Test Block',
            'description' => 'This is a test block.',
            'cell' => null,
            'template' => null,
            'enabled' => true,
        ];

        $this->post('/admin/blocks/add/4', $data);
        $this->assertRedirectContains('/admin/regions/view/4');
        $this->assertFlashMessage('The block has been saved.');
    }

    public function testEdit(): void
    {
        $this->get('/admin/blocks/edit/1');
        $this->assertResponseOk();

        $data = [
            'title' => 'Updated Title',
        ];

        $this->put('/admin/blocks/edit/1', $data);
        $this->assertRedirectContains('/admin/regions/view/3');
        $this->assertFlashMessage('The block has been saved.');
    }

    public function testDelete(): void
    {
        $this->post('/admin/blocks/delete/1');
        $this->assertRedirectContains('/admin/regions/view/3');
        $this->assertFlashMessage('The block has been deleted.');
    }

    public function testMoveUp(): void
    {
        $this->post('/admin/blocks/move-up/1');
        $this->assertRedirectContains('/admin/regions/view/');
        $this->assertFlashMessage('The Block has been moved Up.');
    }

    public function testMoveDown(): void
    {
        $this->post('/admin/blocks/move-down/1');
        $this->assertRedirectContains('/admin/regions/view/');
        $this->assertFlashMessage('The Block has been moved down.');
    }

    public function testConfigGet(): void
    {
        $this->get('/admin/blocks/config/1');
        $this->assertResponseOk();
    }

    public function testConfigPostValid(): void
    {
        $data = [
            'menu' => '3',
            'container' => ['class' => 'nav navbar-nav'],
        ];

        $this->post('/admin/blocks/config/1', $data);

        $this->assertRedirectContains('/admin/regions/view/3');
        $this->assertFlashMessage('Configuration saved');

        $block = $this->fetchTable('Blocks')->get(1);
        $this->assertArrayHasKey('menu', $block->params);
        $this->assertArrayHasKey('container', $block->params);
    }
}
