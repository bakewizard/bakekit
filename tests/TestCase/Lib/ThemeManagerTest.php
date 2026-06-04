<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\ExtensionHandler;
use App\Lib\ThemeManager;
use App\Model\Table\RegionsTable;
use Cake\TestSuite\TestCase;
use Exception;
use Psr\Http\Message\UploadedFileInterface;
use ReflectionProperty;

/**
 * Tests for ThemeManager.
 *
 * ThemeManager is a thin wrapper around ExtensionHandler scoped to the themes
 * directory. It also manages region rows in the database: regions are inserted
 * on install (from the theme's config/regions.php) and deleted on uninstall.
 *
 * ExtensionHandler is always mocked so these tests only cover ThemeManager logic.
 *
 * @uses \App\Lib\ThemeManager
 */
class ThemeManagerTest extends TestCase
{
    /**
     * Fixtures loaded for every test that touches the database.
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Regions',
    ];

    /**
     * Absolute path to the themes directory used by the real application.
     */
    private string $themesDir;

    /**
     * Live ORM table used for database assertions.
     */
    private RegionsTable $regionsTable;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->themesDir = ROOT . DS . 'themes' . DS;
        $this->regionsTable = $this->fetchTable('Regions');
    }

    /**
     * Creates a ThemeManager with an optional ExtensionHandler mock.
     * When no mock is supplied a stub that returns null for every call is used.
     */
    private function makeManager(?ExtensionHandler $ext = null): ThemeManager
    {
        return new ThemeManager(
            $ext ?? $this->createStub(ExtensionHandler::class),
            $this->regionsTable,
        );
    }

    /**
     * Overrides the private $themesDir property via reflection.
     * Used when a test needs to point ThemeManager at a temporary directory.
     */
    private function overrideThemesDir(ThemeManager $manager, string $dir): void
    {
        $ref = new ReflectionProperty(ThemeManager::class, 'themesDir');
        $ref->setAccessible(true);
        $ref->setValue($manager, $dir);
    }

    /**
     * Recursively removes a directory and all of its contents.
     */
    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir), ['.', '..']) as $entry) {
            $path = $dir . DS . $entry;
            is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
        }
        rmdir($dir);
    }

    // -------------------------------------------------------------------------
    // list()
    // -------------------------------------------------------------------------

    /**
     * list() must forward the call to ExtensionHandler::discover() and pass
     * the themes directory as the argument.
     */
    public function testListDelegatesToDiscover(): void
    {
        $expected = ['ThemeA' => ['description' => 'A theme']];

        $ext = $this->createMock(ExtensionHandler::class);
        $ext->expects($this->once())
            ->method('discover')
            ->with($this->themesDir)
            ->willReturn($expected);

        $this->assertSame($expected, $this->makeManager($ext)->list());
    }

    // -------------------------------------------------------------------------
    // view()
    // -------------------------------------------------------------------------

    /**
     * view() must forward the call to ExtensionHandler::readComposerConfig()
     * with the full path to the theme directory (themesDir + theme name).
     */
    public function testViewDelegatesToReadComposerConfig(): void
    {
        $expected = ['name' => 'my-theme'];

        $ext = $this->createMock(ExtensionHandler::class);
        $ext->expects($this->once())
            ->method('readComposerConfig')
            ->with($this->themesDir . 'MyTheme')
            ->willReturn($expected);

        $this->assertSame($expected, $this->makeManager($ext)->view('MyTheme'));
    }

    // -------------------------------------------------------------------------
    // install()
    // -------------------------------------------------------------------------

    /**
     * install() must call ExtensionHandler::load() with the uploaded file and
     * the themes directory, then return the theme name that load() resolved.
     * When no regions.php exists on disk no rows should be inserted.
     */
    public function testInstallDelegatesToLoad(): void
    {
        $file = $this->createStub(UploadedFileInterface::class);

        $ext = $this->createMock(ExtensionHandler::class);
        $ext->expects($this->once())
            ->method('load')
            ->with($file, $this->themesDir)
            ->willReturn('NewTheme');

        $countBefore = $this->regionsTable->find()->count();

        $result = $this->makeManager($ext)->install($file);

        $this->assertSame('NewTheme', $result);
        $this->assertSame($countBefore, $this->regionsTable->find()->count());
    }

    /**
     * When config/regions.php exists and returns an array, install() must
     * insert one region row per entry with the correct alias, description,
     * and theme columns.
     */
    public function testInstallCreatesRegionsFromConfigFile(): void
    {
        $tmpDir = sys_get_temp_dir() . DS . 'bakekit_theme_' . uniqid() . DS;
        $configDir = $tmpDir . 'TestTheme' . DS . 'config' . DS;
        mkdir($configDir, 0777, true);

        $regions = [
            'header' => 'Header region',
            'sidebar' => 'Sidebar region',
            'footer' => 'Footer region',
        ];
        file_put_contents($configDir . 'regions.php', '<?php return ' . var_export($regions, true) . ';');

        $ext = $this->createMock(ExtensionHandler::class);
        $ext->method('load')->willReturn('TestTheme');

        $manager = new ThemeManager($ext, $this->regionsTable);
        $this->overrideThemesDir($manager, $tmpDir);

        $manager->install($this->createStub(UploadedFileInterface::class));

        $created = $this->regionsTable
            ->find()
            ->where(['theme' => 'TestTheme'])
            ->all()
            ->toArray();

        $this->assertCount(3, $created);
        $this->assertEqualsCanonicalizing(
            ['header', 'sidebar', 'footer'],
            array_column($created, 'alias'),
        );

        $this->rmdirRecursive($tmpDir);
    }

    /**
     * When config/regions.php returns a non-array value the method must
     * silently skip region creation without throwing.
     */
    public function testInstallSkipsRegionsWhenConfigIsNotArray(): void
    {
        $tmpDir = sys_get_temp_dir() . DS . 'bakekit_theme_' . uniqid() . DS;
        $configDir = $tmpDir . 'BadTheme' . DS . 'config' . DS;
        mkdir($configDir, 0777, true);
        file_put_contents($configDir . 'regions.php', '<?php return "not an array";');

        $ext = $this->createMock(ExtensionHandler::class);
        $ext->method('load')->willReturn('BadTheme');

        $manager = new ThemeManager($ext, $this->regionsTable);
        $this->overrideThemesDir($manager, $tmpDir);

        $countBefore = $this->regionsTable->find()->count();
        $manager->install($this->createStub(UploadedFileInterface::class));

        $this->assertSame($countBefore, $this->regionsTable->find()->count());

        $this->rmdirRecursive($tmpDir);
    }

    // -------------------------------------------------------------------------
    // uninstall()
    // -------------------------------------------------------------------------

    /**
     * uninstall() must call ExtensionHandler::unload() with the theme name
     * and the themes directory when the theme is not active.
     */
    public function testUninstallDelegatesToUnload(): void
    {
        $ext = $this->createMock(ExtensionHandler::class);
        $ext->expects($this->once())
            ->method('unload')
            ->with('OldTheme', $this->themesDir);

        $this->makeManager($ext)->uninstall('OldTheme', false);
    }

    /**
     * uninstall() must throw when isActive is true and must never call unload().
     * This prevents accidental deletion of the running theme.
     */
    public function testUninstallThrowsWhenThemeIsActive(): void
    {
        $ext = $this->createMock(ExtensionHandler::class);
        $ext->expects($this->never())->method('unload');

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Cannot uninstall active theme/');

        $this->makeManager($ext)->uninstall('ActiveTheme', true);
    }

    /**
     * isActive defaults to false, so calling uninstall() with only the theme
     * name must succeed without throwing.
     */
    public function testUninstallDefaultsToInactive(): void
    {
        $ext = $this->createMock(ExtensionHandler::class);
        $ext->expects($this->once())->method('unload');

        $this->makeManager($ext)->uninstall('InactiveTheme');
    }

    /**
     * uninstall() must delete all regions that belong to the given theme and
     * leave regions that belong to other themes untouched.
     */
    public function testUninstallDeletesOnlyThemeRegions(): void
    {
        $this->regionsTable->saveOrFail($this->regionsTable->newEntity([
            'alias' => 'header',
            'theme' => 'OldTheme',
            'description' => 'Header',
        ]));
        $this->regionsTable->saveOrFail($this->regionsTable->newEntity([
            'alias' => 'footer',
            'theme' => 'OldTheme',
            'description' => 'Footer',
        ]));
        $this->regionsTable->saveOrFail($this->regionsTable->newEntity([
            'alias' => 'sidebar',
            'theme' => 'OtherTheme',
            'description' => 'Sidebar',
        ]));

        $this->makeManager($this->createStub(ExtensionHandler::class))->uninstall('OldTheme', false);

        $this->assertSame(0, $this->regionsTable->find()->where(['theme' => 'OldTheme'])->count());
        $this->assertSame(1, $this->regionsTable->find()->where(['theme' => 'OtherTheme'])->count());
    }
}
