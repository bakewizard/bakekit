<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\Model\Entity\MenuLink;
use App\View\Helper\MenuHelper;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\View;

class MenuHelperTest extends TestCase
{
    protected ?MenuHelper $helper = null;

    protected function setUp(): void
    {
        parent::setUp();

        $request = new ServerRequest(['url' => '/admin']);
        $view = new View($request);
        $this->helper = new MenuHelper($view);
    }

    public function testRenderEmptyMenu(): void
    {
        $result = $this->helper->render([]);
        $this->assertSame('', $result);
    }

    public function testRenderSingleMenuItem(): void
    {
        $item = new MenuLink([
            'title' => 'Dashboard',
            'link' => '/admin',
            'target' => '_self',
            'icon' => '',
            'children' => [],
        ]);

        $html = $this->helper->render([$item]);

        $this->assertStringContainsString('<ul', $html);
        $this->assertStringContainsString('<li', $html);
        $this->assertStringContainsString('Dashboard', $html);
        $this->assertStringContainsString('href="/admin"', $html);
    }

    public function testRenderNestedMenu(): void
    {
        $child = new MenuLink([
            'title' => 'Subitem',
            'link' => '/admin/sub',
            'target' => '_self',
            'icon' => '',
            'children' => [],
        ]);

        $parent = new MenuLink([
            'title' => 'Parent',
            'link' => '/admin/parent',
            'target' => '_self',
            'icon' => '',
            'children' => [$child],
        ]);

        $html = $this->helper->render([$parent]);
        $this->assertStringContainsString('dropdown-menu', $html);
        $this->assertStringContainsString('Subitem', $html);
    }

    public function testRenderWithIcon(): void
    {
        $item = new MenuLink([
            'title' => 'With Icon',
            'link' => '/icon',
            'target' => '_self',
            'icon' => 'fas fa-star',
            'children' => [],
        ]);

        $html = $this->helper->render([$item]);
        $this->assertStringContainsString('<i class="fas fa-star"></i>', $html);
    }

    public function testActiveLinkDetection(): void
    {
        $item = new MenuLink([
            'title' => 'Active',
            'link' => '/admin',
            'target' => '_self',
            'icon' => '',
            'children' => [],
        ]);

        $html = $this->helper->render([$item]);
        $this->assertStringContainsString('active', $html);
    }
}
