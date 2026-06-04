<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Lib\ComposerManager;
use App\Lib\ThemeManager;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Exception;
use Laminas\Diactoros\UploadedFile;

/**
 * App\Controller\Admin\ThemesController Test Case
 *
 * @uses \App\Controller\Admin\ThemesController
 */
class ThemesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Settings',
        'app.Regions',
        'app.Blocks',
    ];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
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

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // index()
    // -------------------------------------------------------------------------

    /**
     * index() renders the theme list and exposes the $themes view variable.
     *
     * @uses \App\Controller\Admin\ThemesController::index()
     */
    public function testIndex(): void
    {
        $this->mockService(ThemeManager::class, function () {
            $mock = $this->getMockBuilder(ThemeManager::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['list'])
                ->getMock();

            $mock->expects($this->once())
                ->method('list')
                ->willReturn([
                    'DefaultTheme' => [
                        'name' => 'DefaultTheme',
                        'description' => 'The default theme.',
                        'license' => 'MIT',
                    ],
                    'ModernTheme' => [
                        'name' => 'ModernTheme',
                        'description' => 'A modern responsive theme.',
                        'license' => 'MIT',
                    ],
                ]);

            return $mock;
        });

        Configure::write('System.theme', 'DefaultTheme');

        $this->get('/admin/themes');
        $this->assertResponseOk();
        $this->assertResponseContains('DefaultTheme');
        $this->assertResponseContains('ModernTheme');
        $this->assertNotNull($this->viewVariable('themes'));
    }

    // -------------------------------------------------------------------------
    // view()
    // -------------------------------------------------------------------------

    /**
     * view() renders theme details from its composer.json metadata.
     *
     * @uses \App\Controller\Admin\ThemesController::view()
     */
    public function testView(): void
    {
        $this->mockService(ThemeManager::class, function () {
            $mock = $this->getMockBuilder(ThemeManager::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['view'])
                ->getMock();

            $mock->expects($this->once())
                ->method('view')
                ->with('ModernTheme')
                ->willReturn([
                    'name' => 'ModernTheme',
                    'description' => 'A modern responsive theme.',
                    'license' => 'MIT',
                ]);

            return $mock;
        });

        $this->get('/admin/themes/view/ModernTheme');
        $this->assertResponseOk();
        $this->assertResponseContains('ModernTheme');
        $this->assertResponseContains('A modern responsive theme.');
        $this->assertResponseContains('MIT');
    }

    // -------------------------------------------------------------------------
    // install()
    // -------------------------------------------------------------------------

    /**
     * install() calls ThemeManager::install(), dumps autoload, and shows a
     * success flash message on a valid upload.
     *
     * @uses \App\Controller\Admin\ThemesController::install()
     */
    public function testInstallSuccess(): void
    {
        $this->mockService(ThemeManager::class, function () {
            $mock = $this->getMockBuilder(ThemeManager::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['install'])
                ->getMock();

            $mock->expects($this->once())
                ->method('install')
                ->willReturn('ModernTheme');

            return $mock;
        });

        $this->mockService(ComposerManager::class, function () {
            $mock = $this->createMock(ComposerManager::class);
            $mock->expects($this->once())
                ->method('dumpAutoload')
                ->with(['--optimize' => true]);

            return $mock;
        });

        $tmpPath = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($tmpPath, 'fake content');

        $this->configRequest([
            'files' => [
                'theme' => new UploadedFile(
                    $tmpPath,
                    filesize($tmpPath),
                    UPLOAD_ERR_OK,
                    'theme.zip',
                    'application/zip',
                ),
            ],
        ]);

        $this->post('/admin/themes/install');
        $this->assertRedirect('/admin/themes');
        $this->assertFlashMessage('The theme "ModernTheme" has been installed.');
    }

    /**
     * install() shows an error flash and redirects when no file is provided.
     *
     * @uses \App\Controller\Admin\ThemesController::install()
     */
    public function testInstallNoFile(): void
    {
        $this->post('/admin/themes/install', []);
        $this->assertRedirect('/admin/themes');
        $this->assertFlashMessage('No file was uploaded.');
    }

    /**
     * install() shows the exception message as an error flash when
     * ThemeManager::install() throws.
     *
     * @uses \App\Controller\Admin\ThemesController::install()
     */
    public function testInstallHandlesException(): void
    {
        $this->mockService(ThemeManager::class, function () {
            $mock = $this->getMockBuilder(ThemeManager::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['install'])
                ->getMock();

            $mock->method('install')
                ->willThrowException(new Exception('Invalid theme ZIP.'));

            return $mock;
        });

        $this->mockService(ComposerManager::class, function () {
            return $this->createMock(ComposerManager::class);
        });

        $tmpPath = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($tmpPath, 'bad content');

        $this->configRequest([
            'files' => [
                'theme' => new UploadedFile(
                    $tmpPath,
                    filesize($tmpPath),
                    UPLOAD_ERR_OK,
                    'bad.zip',
                    'application/zip',
                ),
            ],
        ]);

        $this->post('/admin/themes/install');
        $this->assertRedirect('/admin/themes');
        $this->assertFlashMessage('Invalid theme ZIP.');
    }

    // -------------------------------------------------------------------------
    // uninstall()
    // -------------------------------------------------------------------------

    /**
     * uninstall() passes isActive=false when the target theme differs from the
     * active one, then dumps autoload and shows a success flash.
     *
     * @uses \App\Controller\Admin\ThemesController::uninstall()
     */
    public function testUninstallSuccess(): void
    {
        $this->mockService(ThemeManager::class, function () {
            $mock = $this->getMockBuilder(ThemeManager::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['uninstall'])
                ->getMock();

            // Active theme is DefaultTheme, so ModernTheme must receive isActive=false.
            $mock->expects($this->once())
                ->method('uninstall')
                ->with('ModernTheme', false);

            return $mock;
        });

        $this->mockService(ComposerManager::class, function () {
            $mock = $this->createMock(ComposerManager::class);
            $mock->expects($this->once())
                ->method('dumpAutoload')
                ->with(['--optimize' => true]);

            return $mock;
        });

        $this->delete('/admin/themes/uninstall/ModernTheme');
        $this->assertRedirect('/admin/themes');
        $this->assertFlashMessage('The theme "ModernTheme" has been unnstalled.');
    }

    /**
     * When ThemeManager::uninstall() throws (e.g. because the theme is active),
     * the controller's catch block must write the exception message to flash as
     * an error and redirect to index.
     *
     * The isActive flag logic ($name === $activeTheme) is covered by
     * ThemeManagerTest::testUninstallThrowsWhenThemeIsActive().
     *
     * @uses \App\Controller\Admin\ThemesController::uninstall()
     */
    public function testUninstallActiveThemeShowsError(): void
    {
        // The active theme comparison happens inside the request's bootstrapped
        // Configure context. Rather than trying to inject the active theme value,
        // we mock uninstall() to throw unconditionally and verify the controller's
        // catch block writes the message to flash.
        $this->mockService(ThemeManager::class, function () {
            $mock = $this->getMockBuilder(ThemeManager::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['uninstall'])
                ->getMock();

            $mock->expects($this->once())
                ->method('uninstall')
                ->willThrowException(new Exception('Cannot uninstall active theme. Deactivate it first.'));

            return $mock;
        });

        $this->mockService(ComposerManager::class, function () {
            return $this->createMock(ComposerManager::class);
        });

        $this->delete('/admin/themes/uninstall/ModernTheme');
        $this->assertRedirect('/admin/themes');
        $this->assertFlashElement('flash/error');
        $this->assertFlashMessage('Cannot uninstall active theme. Deactivate it first.');
    }

    // -------------------------------------------------------------------------
    // activate()
    // -------------------------------------------------------------------------

    /**
     * activate() writes the theme name to config and redirects to index.
     *
     * @uses \App\Controller\Admin\ThemesController::activate()
     */
    public function testActivate(): void
    {
        $this->put('/admin/themes/activate/ModernTheme');
        $this->assertRedirect('/admin/themes');
        $this->assertEquals('ModernTheme', Configure::read('theme'));
    }

    /**
     * activate() called without a name writes null to config (deactivates theme).
     *
     * @uses \App\Controller\Admin\ThemesController::activate()
     */
    public function testActivateWithNullDeactivatesTheme(): void
    {
        Configure::write('System.theme', 'ModernTheme');

        $this->put('/admin/themes/activate');
        $this->assertRedirect('/admin/themes');
        $this->assertNull(Configure::read('theme'));
    }

    // -------------------------------------------------------------------------
    // blocks()
    // -------------------------------------------------------------------------

    /**
     * blocks() renders the blocks management page for the active theme and
     * exposes $regions and $activeTheme view variables.
     *
     * The fixture theme 'DefaultTheme' must exist in app.Regions so that
     * findByTheme() returns a non-empty result without a 500.
     *
     * @uses \App\Controller\Admin\ThemesController::blocks()
     */
    public function testBlocks(): void
    {
        $this->get('/admin/themes/blocks');
        $this->assertResponseOk();
        $this->assertNotNull($this->viewVariable('regions'));
        $this->assertNotNull($this->viewVariable('activeTheme'));
    }
}
