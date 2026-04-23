<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Lib\ComposerManager;
use App\Lib\ThemeManager;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
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
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Settings',
    ];

    /**
     * setUp method
     *
     * This method is called before each test method.
     *
     * @return void
     */
    protected function setUp(): void
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

    /**
     * tearDown method
     *
     * This method is called after each test method.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test view method
     *
     * @return void
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

    /**
     * Test view method
     *
     * @return void
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

        $testFile = new UploadedFile(
            $tmpPath,
            filesize($tmpPath),
            UPLOAD_ERR_OK,
            'theme.zip',
            'application/zip',
        );

        $this->configRequest([
            'files' => [
                'theme' => $testFile,
            ],
        ]);

        $this->post('/admin/themes/install');

        $this->assertRedirect('/admin/themes');
        $this->assertFlashMessage('The theme "ModernTheme" has been installed.');
    }

    public function testInstallNoFile(): void
    {
        $this->post('/admin/themes/install', []);
        $this->assertRedirect('/admin/themes');
        $this->assertFlashMessage('No file was uploaded.');
    }

    public function testUninstallSuccess(): void
    {
        Configure::write('System.theme', 'DefaultTheme');

        $this->mockService(ThemeManager::class, function () {
            $mock = $this->getMockBuilder(ThemeManager::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['uninstall'])
                ->getMock();

            $mock->expects($this->once())
                ->method('uninstall')
                ->with('ModernTheme');

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

    public function testActivate(): void
    {
        $name = 'ModernTheme';

        $this->put("/admin/themes/activate/{$name}");

        $this->assertRedirect('/admin/themes');
        $this->assertEquals($name, Configure::read('theme'));
    }
}
