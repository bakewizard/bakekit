<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\ExtensionHandler;
use App\Lib\ThemeManager;
use Cake\TestSuite\TestCase;
use Exception;
use Psr\Http\Message\UploadedFileInterface;

/**
 * App\Lib\ThemeManager Test Case
 *
 * ThemeManager is a thin wrapper around ExtensionHandler scoped to the themes directory.
 * Tests verify that each method delegates correctly and that uninstall() enforces
 * the "cannot uninstall active theme" rule.
 *
 * @uses \App\Lib\ThemeManager
 */
class ThemeManagerTest extends TestCase
{
    private string $themesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->themesDir = ROOT . DS . 'themes' . DS;
    }

    private function makeManager(?ExtensionHandler $ext = null): ThemeManager
    {
        return new ThemeManager($ext ?? $this->createStub(ExtensionHandler::class));
    }

    // -------------------------------------------------------------------------
    // list()
    // -------------------------------------------------------------------------

    /**
     * list() delegates to ExtensionHandler::discover() with the themes directory.
     */
    public function testListDelegatesToDiscover(): void
    {
        $expected = ['ThemeA' => ['description' => 'A theme']];

        $ext = $this->createMock(ExtensionHandler::class);
        $ext->expects($this->once())
            ->method('discover')
            ->with($this->themesDir)
            ->willReturn($expected);

        $result = $this->makeManager($ext)->list();
        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // view()
    // -------------------------------------------------------------------------

    /**
     * view() delegates to ExtensionHandler::readComposerConfig() with the correct path.
     */
    public function testViewDelegatesToReadComposerConfig(): void
    {
        $expected = ['name' => 'my-theme'];

        $ext = $this->createMock(ExtensionHandler::class);
        $ext->expects($this->once())
            ->method('readComposerConfig')
            ->with($this->themesDir . 'MyTheme')
            ->willReturn($expected);

        $result = $this->makeManager($ext)->view('MyTheme');
        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // install()
    // -------------------------------------------------------------------------

    /**
     * install() delegates to ExtensionHandler::load() with the themes directory.
     */
    public function testInstallDelegatesToLoad(): void
    {
        $file = $this->createStub(UploadedFileInterface::class);

        $ext = $this->createMock(ExtensionHandler::class);
        $ext->expects($this->once())
            ->method('load')
            ->with($file, $this->themesDir)
            ->willReturn('NewTheme');

        $result = $this->makeManager($ext)->install($file);
        $this->assertSame('NewTheme', $result);
    }

    // -------------------------------------------------------------------------
    // uninstall()
    // -------------------------------------------------------------------------

    /**
     * uninstall() delegates to ExtensionHandler::unload() when theme is not active.
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
     * uninstall() throws when the theme is currently active.
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
     * uninstall() defaults to isActive=false — does not throw for inactive theme.
     */
    public function testUninstallDefaultsToInactive(): void
    {
        $ext = $this->createMock(ExtensionHandler::class);
        $ext->expects($this->once())->method('unload');

        $this->makeManager($ext)->uninstall('InactiveTheme');
    }
}
