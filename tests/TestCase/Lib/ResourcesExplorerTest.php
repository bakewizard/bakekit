<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\ResourcesExplorer;
use Cake\TestSuite\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

class ResourcesExplorerTest extends TestCase
{
    /**
     * The name of the plugin being tested
     *
     * @var string
     */
    private const string PLUGIN_NAME = 'BareBone';

    /**
     * The path to the plugin directory
     *
     * @var string
     */
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

    public function testGetResources(): void
    {
        $result = $this->explorer->getResources([self::PLUGIN_NAME]);

        $this->assertArrayHasKey(self::PLUGIN_NAME, $result);
        $this->assertArrayHasKey('Articles', $result[self::PLUGIN_NAME]);

        $actions = $result[self::PLUGIN_NAME]['Articles'];
        $this->assertContains('index', $actions);
        $this->assertContains('add', $actions);
        $this->assertContains('edit', $actions);
        $this->assertContains('delete', $actions);
    }

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

        // обидва методи ArticleCell знайдені
        $paths = array_column($result[self::PLUGIN_NAME], 'path');
        $this->assertContains('BareBone.Article::recent', $paths);
        $this->assertContains('BareBone.Article::popular', $paths);
    }

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

        // index — пряме посилання
        $selfLinks = array_filter($result[self::PLUGIN_NAME], fn($l) => $l['target'] === '_self');
        $this->assertNotEmpty($selfLinks);

        // view з picker — модалка
        $blankLinks = array_filter($result[self::PLUGIN_NAME], fn($l) => $l['target'] === '_blank');
        $this->assertNotEmpty($blankLinks);

        // category без #[Link] — не потрапляє
        $actions = array_column(array_column($result[self::PLUGIN_NAME], 'url'), 'action');
        $this->assertNotContains('category', $actions);
    }

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

        // Dashboard завжди першим
        $this->assertEquals('Dashboard', $result[self::PLUGIN_NAME][0]['controller']);

        // Dashboard має index і settings
        $dashboard = $result[self::PLUGIN_NAME][0];
        $this->assertContains('index', $dashboard['actions']);
        $this->assertContains('settings', $dashboard['actions']);

        // Articles має index і add, але не edit/delete (мають параметри)
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
