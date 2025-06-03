<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\ExtensionHandler;
use App\Lib\PluginManager;
use App\Lib\ResourcesExplorer;
use App\Model\Table\ResourcesTable;
use App\Model\Table\SettingsTable;
use Cake\TestSuite\TestCase;
use Migrations\Migrations;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Filesystem\Filesystem;

class PluginManagerTest extends TestCase
{
    private PluginManager $PluginManager;
    private $ExtensionHandler;
    private $ResourcesExplorer;
    private $Migrations;
    private $SettingsTable;
    private $ResourcesTable;

    private string $migrationDir;

    public function setUp(): void
    {
        parent::setUp();

        $this->ExtensionHandler = $this->createMock(ExtensionHandler::class);
        $this->ResourcesExplorer = $this->createMock(ResourcesExplorer::class);
        $this->Migrations = $this->createMock(Migrations::class);
        $this->SettingsTable = $this->createMock(SettingsTable::class);
        $this->ResourcesTable = $this->createMock(ResourcesTable::class);

        $this->PluginManager = new PluginManager(
            $this->ExtensionHandler,
            $this->ResourcesExplorer,
            $this->Migrations,
            $this->SettingsTable,
            $this->ResourcesTable,
        );

        $this->migrationDir = ROOT . DS . 'plugins' . DS . 'FakePlugin' . DS . 'config' . DS . 'Migrations';

        if (!is_dir($this->migrationDir)) {
            mkdir($this->migrationDir, 0777, true);
        }
    }

    public function tearDown(): void
    {
        $fs = new Filesystem();
        if ($fs->exists(ROOT . DS . 'plugins' . DS . 'FakePlugin')) {
            $fs->remove(ROOT . DS . 'plugins' . DS . 'FakePlugin');
        }

        parent::tearDown();
    }

    public function testList(): void
    {
        $expected = [
            'PluginA' => ['name' => 'PluginA'],
        ];
        $this->ExtensionHandler->method('discover')->willReturn($expected);

        $result = $this->PluginManager->list();
        $this->assertSame($expected, $result);
    }

    public function testInstall(): void
    {
        $file = $this->createMock(UploadedFileInterface::class);
        $this->ExtensionHandler->expects($this->once())
            ->method('load')
            ->with($file)
            ->willReturn('ExamplePlugin');

        $result = $this->PluginManager->install($file);
        $this->assertSame('ExamplePlugin', $result);
    }

    public function testActivate(): void
    {
        $plugin = 'FakePlugin';

        $this->Migrations->expects($this->once())->method('migrate')->willReturn(true);
        $this->Migrations->expects($this->once())->method('seed')->willReturn(true);
        $this->ResourcesExplorer->expects($this->once())->method('getResources')->willReturn([]);
        $this->ResourcesTable->expects($this->once())->method('addResources')->with([]);

        $this->ExtensionHandler->expects($this->once())
            ->method('readComposerConfig')
            ->with($this->stringContains($plugin))
            ->willReturn(['name' => $plugin]);

        $result = $this->PluginManager->activate($plugin);
        $this->assertSame(['name' => $plugin], $result);
    }

    public function testUninstallActive(): void
    {
        $plugin = 'PluginToRemove';

        $this->Migrations->expects($this->once())->method('rollback')->with(['plugin' => $plugin]);
        $this->SettingsTable->expects($this->once())->method('deleteAll')->with(['namespace' => $plugin]);
        $this->ResourcesTable->expects($this->once())->method('deleteResources')->with($plugin);
        $this->ExtensionHandler->expects($this->once())->method('unload')->with($plugin);

        mkdir(ROOT . DS . 'plugins' . DS . $plugin . DS . 'config' . DS . 'Migrations', 0777, true);

        $this->PluginManager->uninstall($plugin, true);

        // Cleanup mock plugin dir
        rmdir(ROOT . DS . 'plugins' . DS . $plugin . DS . 'config' . DS . 'Migrations');
        rmdir(ROOT . DS . 'plugins' . DS . $plugin . DS . 'config');
        rmdir(ROOT . DS . 'plugins' . DS . $plugin);
    }

    public function testAddSettingsWithForm(): void
    {
        require_once ROOT . '/tests/test_app/plugins/FakePlugin/src/Form/ConfigForm.php';

        $settingsTable = $this->getMockBuilder(SettingsTable::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();

        $resourcesTable = $this->createMock(ResourcesTable::class);
        $extensionHandler = $this->createMock(ExtensionHandler::class);
        $resourcesExplorer = $this->createMock(ResourcesExplorer::class);
        $migrations = $this->createMock(Migrations::class);

        $pluginManager = new PluginManager(
            $extensionHandler,
            $resourcesExplorer,
            $migrations,
            $settingsTable,
            $resourcesTable,
        );

        // Expectation: Form::execute() calls SettingsTable::save() (indirectly, so we just want it to run)
        // You can assert more by mocking the actual method in ConfigForm if needed.

        // Act: call addSettings()
        $pluginManager->addSettings('FakePlugin');

        // Assert: nothing crashes, class loads, and logic executes
        $this->assertTrue(true);
    }

    public function testAddMigrationsWhenPathMissing(): void
    {
        $result = $this->PluginManager->addMigrations('NonexistentPlugin');
        $this->assertFalse($result);
    }
}
