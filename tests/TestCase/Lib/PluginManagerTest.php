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
    private string $migrationDir;

    public function setUp(): void
    {
        parent::setUp();

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

    private function makePluginManager(
        ?ExtensionHandler $ext = null,
        ?ResourcesExplorer $re = null,
        ?Migrations $mig = null,
        ?SettingsTable $set = null,
        ?ResourcesTable $res = null,
    ): PluginManager {
        return new PluginManager(
            $ext ?? $this->createStub(ExtensionHandler::class),
            $re ?? $this->createStub(ResourcesExplorer::class),
            $mig ?? $this->createStub(Migrations::class),
            $set ?? $this->createStub(SettingsTable::class),
            $res ?? $this->createStub(ResourcesTable::class),
        );
    }

    public function testList(): void
    {
        $expected = ['PluginA' => ['name' => 'PluginA']];

        $ext = $this->createMock(ExtensionHandler::class);
        $ext->expects($this->once())->method('discover')->willReturn($expected);

        $result = $this->makePluginManager(ext: $ext)->list();
        $this->assertSame($expected, $result);
    }

    public function testInstall(): void
    {
        $file = $this->createStub(UploadedFileInterface::class);

        $ext = $this->createMock(ExtensionHandler::class);
        $ext->expects($this->once())
            ->method('load')
            ->with($file)
            ->willReturn('ExamplePlugin');

        $result = $this->makePluginManager(ext: $ext)->install($file);
        $this->assertSame('ExamplePlugin', $result);
    }

    public function testActivate(): void
    {
        $plugin = 'FakePlugin';

        $mig = $this->createMock(Migrations::class);
        $mig->expects($this->once())->method('migrate')->willReturn(true);
        $mig->expects($this->once())->method('seed')->willReturn(true);

        $re = $this->createMock(ResourcesExplorer::class);
        $re->expects($this->once())->method('getResources')->willReturn([]);

        $res = $this->createMock(ResourcesTable::class);
        $res->expects($this->once())->method('addResources')->with([]);

        $ext = $this->createMock(ExtensionHandler::class);
        $ext->expects($this->once())
            ->method('readComposerConfig')
            ->with($this->stringContains($plugin))
            ->willReturn(['name' => $plugin]);

        $result = $this->makePluginManager(ext: $ext, re: $re, mig: $mig, res: $res)->activate($plugin);
        $this->assertSame(['name' => $plugin], $result);
    }

    public function testUninstallActive(): void
    {
        $plugin = 'PluginToRemove';

        $mig = $this->createMock(Migrations::class);
        $mig->expects($this->once())->method('rollback')->with(['plugin' => $plugin]);

        $set = $this->createMock(SettingsTable::class);
        $set->expects($this->once())->method('deleteAll')->with(['namespace' => $plugin]);

        $res = $this->createMock(ResourcesTable::class);
        $res->expects($this->once())->method('deleteResources')->with($plugin);

        $ext = $this->createMock(ExtensionHandler::class);
        $ext->expects($this->once())->method('unload')->with($plugin);

        mkdir(ROOT . DS . 'plugins' . DS . $plugin . DS . 'config' . DS . 'Migrations', 0777, true);

        $this->makePluginManager(ext: $ext, mig: $mig, set: $set, res: $res)->uninstall($plugin, true);

        rmdir(ROOT . DS . 'plugins' . DS . $plugin . DS . 'config' . DS . 'Migrations');
        rmdir(ROOT . DS . 'plugins' . DS . $plugin . DS . 'config');
        rmdir(ROOT . DS . 'plugins' . DS . $plugin);
    }

    public function testAddSettingsWithForm(): void
    {
        require_once ROOT . '/tests/test_app/plugins/FakePlugin/src/Form/ConfigForm.php';

        $set = $this->getMockBuilder(SettingsTable::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();
        $set->expects($this->never())->method('save');

        $this->makePluginManager(set: $set)->addSettings('FakePlugin');

        $this->assertTrue(true);
    }

    public function testAddMigrationsWhenPathMissing(): void
    {
        $result = $this->makePluginManager()->addMigrations('NonexistentPlugin');
        $this->assertFalse($result);
    }
}
