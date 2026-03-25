<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\ResourcesExplorer;
use Cake\TestSuite\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

class ResourcesExplorerTest extends TestCase
{
    private const string PLUGIN_NAME = 'BareBone';
    private const string PLUGINS_DIR = ROOT . DS . 'plugins' . DS;

    protected ResourcesExplorer $explorer;

    public function setUp(): void
    {
        parent::setUp();

        $zipPath = TESTS . 'Fixture/data/' . self::PLUGIN_NAME . '.zip';
        $zip = new ZipArchive();
        $res = $zip->open($zipPath);
        $this->assertTrue($res === true, 'ZIP file could not be opened');
        $zip->extractTo(self::PLUGINS_DIR);
        $zip->close();

        /** @var \Composer\Autoload\ClassLoader $loader */
        $loader = require ROOT . '/vendor/autoload.php';
        $loader->addPsr4('BareBone\\', self::PLUGINS_DIR . 'BareBone' . DS . 'src');

        $this->explorer = new ResourcesExplorer();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $pluginDir = self::PLUGINS_DIR . self::PLUGIN_NAME . DS;
        $fs = new Filesystem();
        if ($fs->exists($pluginDir)) {
            $fs->remove($pluginDir);
        }
    }

    // -------------------------------------------------------------------------
    // getResources()
    // -------------------------------------------------------------------------

    /**
     * getResources() should return only methods marked with #[Resource].
     * Format: [plugin => [controller => [action => label]]].
     */
    public function testGetResources(): void
    {
        $result = $this->explorer->getResources([self::PLUGIN_NAME]);

        $this->assertArrayHasKey(self::PLUGIN_NAME, $result);
        $this->assertArrayHasKey('Articles', $result[self::PLUGIN_NAME]);

        $actions = $result[self::PLUGIN_NAME]['Articles'];

        // Keys are action names, values are labels
        $this->assertArrayHasKey('index', $actions);
        $this->assertArrayHasKey('add', $actions);
        $this->assertArrayHasKey('edit', $actions);
        $this->assertArrayHasKey('delete', $actions);

        // Labels are stored correctly
        $this->assertEquals('List articles', $actions['index']);
        $this->assertEquals('Create an article', $actions['add']);
        $this->assertEquals('Edit an article', $actions['edit']);
        $this->assertEquals('Delete an article', $actions['delete']);
    }

    /**
     * Methods without #[Resource] should not appear in the result.
     */
    public function testGetResourcesExcludesMethodsWithoutAttribute(): void
    {
        $result = $this->explorer->getResources([self::PLUGIN_NAME]);

        $actions = $result[self::PLUGIN_NAME]['Articles'];

        // view() has no #[Resource] attribute
        $this->assertArrayNotHasKey('view', $actions);
    }

    /**
     * Controllers with no #[Resource] methods should not appear in the result.
     * Plugins with no eligible controllers should not appear either.
     */
    public function testGetResourcesSkipsEmptyControllers(): void
    {
        $result = $this->explorer->getResources([self::PLUGIN_NAME]);

        // AppController and controllers with no #[Resource] methods are excluded
        $this->assertArrayNotHasKey('App', $result[self::PLUGIN_NAME] ?? []);
    }

    /**
     * Dashboard controller with #[Resource] methods should appear.
     */
    public function testGetResourcesIncludesDashboard(): void
    {
        $result = $this->explorer->getResources([self::PLUGIN_NAME]);

        $this->assertArrayHasKey('Dashboard', $result[self::PLUGIN_NAME]);
        $this->assertArrayHasKey('index', $result[self::PLUGIN_NAME]['Dashboard']);
        $this->assertEquals('View dashboard', $result[self::PLUGIN_NAME]['Dashboard']['index']);
    }

    // -------------------------------------------------------------------------
    // getCells()
    // -------------------------------------------------------------------------

    public function testGetCells(): void
    {
        $result = $this->explorer->getCells([self::PLUGIN_NAME]);

        $this->assertArrayHasKey(self::PLUGIN_NAME, $result);
        $this->assertNotEmpty($result[self::PLUGIN_NAME]);

        foreach ($result[self::PLUGIN_NAME] as $cell) {
            $this->assertArrayHasKey('summary', $cell);
            $this->assertArrayHasKey('description', $cell);
            $this->assertArrayHasKey('path', $cell);
            $this->assertNotEmpty($cell['summary']);
        }

        $paths = array_column($result[self::PLUGIN_NAME], 'path');
        $this->assertContains('BareBone.Article::recent', $paths);
        $this->assertContains('BareBone.Article::popular', $paths);
    }

    // -------------------------------------------------------------------------
    // getLinks()
    // -------------------------------------------------------------------------

    public function testGetLinks(): void
    {
        $result = $this->explorer->getLinks([self::PLUGIN_NAME]);

        $this->assertArrayHasKey(self::PLUGIN_NAME, $result);
        $this->assertNotEmpty($result[self::PLUGIN_NAME]);

        foreach ($result[self::PLUGIN_NAME] as $link) {
            $this->assertArrayHasKey('summary', $link);
            $this->assertArrayHasKey('description', $link);
            $this->assertArrayHasKey('url', $link);
            $this->assertArrayHasKey('target', $link);
            $this->assertIsArray($link['url']);
            $this->assertNotEmpty($link['summary']);
        }

        // Direct links
        $selfLinks = array_filter($result[self::PLUGIN_NAME], fn($l) => $l['target'] === '_self');
        $this->assertNotEmpty($selfLinks);

        // Modal links (picker)
        $blankLinks = array_filter($result[self::PLUGIN_NAME], fn($l) => $l['target'] === '_blank');
        $this->assertNotEmpty($blankLinks);

        // Methods without #[Link] should not appear
        $actions = array_column(array_column($result[self::PLUGIN_NAME], 'url'), 'action');
        $this->assertNotContains('category', $actions);
    }

    // -------------------------------------------------------------------------
    // getAdminLinks()
    // -------------------------------------------------------------------------

    public function testGetAdminLinks(): void
    {
        $result = $this->explorer->getAdminLinks([self::PLUGIN_NAME]);

        $this->assertArrayHasKey(self::PLUGIN_NAME, $result);
        $this->assertNotEmpty($result[self::PLUGIN_NAME]);

        foreach ($result[self::PLUGIN_NAME] as $item) {
            $this->assertArrayHasKey('controller', $item);
            $this->assertArrayHasKey('actions', $item);
            $this->assertArrayHasKey('url', $item);
            $this->assertIsArray($item['actions']);
            $this->assertNotEmpty($item['actions']);
        }

        // Dashboard is always first
        $this->assertEquals('Dashboard', $result[self::PLUGIN_NAME][0]['controller']);

        // Dashboard has index and settings
        $dashboard = $result[self::PLUGIN_NAME][0];
        $this->assertContains('index', $dashboard['actions']);
        $this->assertContains('settings', $dashboard['actions']);

        // Articles has index and add, but not edit/delete (they require parameters)
        $articles = array_values(array_filter(
            $result[self::PLUGIN_NAME],
            fn($i) => $i['controller'] === 'Articles',
        ))[0] ?? null;
        $this->assertNotNull($articles);
        $this->assertContains('index', $articles['actions']);
        $this->assertContains('add', $articles['actions']);
        $this->assertNotContains('edit', $articles['actions']);
        $this->assertNotContains('delete', $articles['actions']);
    }
}
