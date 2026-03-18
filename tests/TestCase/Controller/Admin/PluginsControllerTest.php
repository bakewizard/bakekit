<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Lib\ComposerManager;
use App\Lib\PluginManager;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Exception;
use Laminas\Diactoros\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

/**
 * App\Controller\Admin\PluginsController Test Case
 *
 * @uses \App\Controller\Admin\PluginsController
 */
class PluginsControllerTest extends TestCase
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
        'app.Plugins',
    ];

    /**
     * The path to the plugin directory
     *
     * @var string
     */
    private const string PLUGINS_DIR = ROOT . DS . 'plugins' . DS;

    /**
     * The name of the plugin being tested
     *
     * @var string
     */
    private const string PLUGIN_NAME = 'BareBone';

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

        Cache::disable();
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

        // Clean up temporary plugin directory if it was created and not using vfsStream
        $pluginDir = self::PLUGINS_DIR . self::PLUGIN_NAME . DS;
        $fs = new Filesystem();
        if ($fs->exists($pluginDir)) {
            $fs->remove($pluginDir);
        }
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\Admin\PluginsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/admin/plugins');

        $this->assertResponseOk();
        $this->assertNotNull($this->viewVariable('plugins'));
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\Admin\PluginsController::edit()
     */
    public function testEditGet(): void
    {
        $this->get('/admin/plugins/edit/1');
        $this->assertResponseOk();
    }

    /**
     * Test edit post method
     *
     * @return void
     * @uses \App\Controller\Admin\PluginsController::edit()
     */
    public function testEditPost(): void
    {
        $postData = [
            'alias' => 'test',
        ];
        $this->post('/admin/plugins/edit/1', $postData);
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/plugins');
    }

    /**
     * Test add post method
     *
     * @return void
     * @uses \App\Controller\Admin\PluginsController::add()
     */
    public function testInstall(): void
    {
        $zipPath = TESTS . 'Fixture/data/' . self::PLUGIN_NAME . '.zip';
        $tmpName = tempnam(sys_get_temp_dir(), 'php');
        copy($zipPath, $tmpName);

        $this->assertFileExists($tmpName, 'The plugin zip file does not exist.');

        // Ensure the plugin directory does NOT exist before starting the test
        $pluginDir = self::PLUGINS_DIR . self::PLUGIN_NAME;
        $this->assertDirectoryDoesNotExist($pluginDir, 'The plugin directory already exists.');

        $this->mockService(ComposerManager::class, function () {
            $mock = $this->createMock(ComposerManager::class);
            $mock->expects($this->once())
                ->method('dumpAutoload')
                ->with(['--optimize' => true]);

            return $mock;
        });

        $zipFile = new UploadedFile(
            $tmpName,
            filesize($tmpName),
            UPLOAD_ERR_OK,
            self::PLUGIN_NAME . '.zip',
            'application/zip',
        );

        $this->configRequest([
            'files' => [
                'plugin' => $zipFile,
            ],
        ]);

        $this->post('/admin/plugins/install');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/plugins');
        $this->assertFlashMessage('The plugin "' . self::PLUGIN_NAME . '" has been installed.');
        $this->assertDirectoryExists(ROOT . '/plugins/' . self::PLUGIN_NAME, 'The plugin directory does not exist.');
    }

    /**
     * Test install method with no file uploaded
     *
     * @return void
     * @uses \App\Controller\Admin\PluginsController::install()
     */
    public function testInstallNoFileUploaded(): void
    {
        $zipFile = new UploadedFile(
            '',
            0,
            UPLOAD_ERR_NO_FILE,
            '',
            '',
        );

        // Simulate a file upload with no file
        $this->configRequest([
            'files' => [
                'plugin' => $zipFile,
            ],
        ]);

        $this->post('/admin/plugins/install');

        $this->assertFlashMessage('No file was uploaded.');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/plugins');
    }

    /**
     * Test activate new method
     *
     * @return void
     * @uses \App\Controller\Admin\PluginsController::activate()
     */
    public function testActivateNewPlugin(): void
    {
        $zipPath = TESTS . 'Fixture/data/' . self::PLUGIN_NAME . '.zip';

        $zip = new ZipArchive();
        $res = $zip->open($zipPath);
        $this->assertTrue($res === true, 'ZIP file could not be opened');
        $zip->extractTo(self::PLUGINS_DIR);
        $zip->close();

        $this->assertDirectoryExists(self::PLUGINS_DIR, 'Plugin directory should exist after extraction');

        $this->mockService(PluginManager::class, function () {
            $mock = $this->createMock(PluginManager::class);
            $mock->expects($this->once())
                ->method('activate')
                ->with(self::PLUGIN_NAME);

            return $mock;
        });

        $this->post('/admin/plugins/activate/' . self::PLUGIN_NAME);

        $this->assertResponseSuccess();
        $this->assertRedirectContains('/admin/plugins');
        $this->assertFlashMessage('The plugin has been activated.');

        $plugin = $this->fetchTable('Plugins')->find()
            ->where(['name' => self::PLUGIN_NAME])
            ->first();

        $this->assertNotEmpty($plugin, 'Plugin record should be created');
        $this->assertTrue($plugin->enabled, 'Plugin should be enabled');
    }

    /**
     * Test activate method
     *
     * @return void
     * @uses \App\Controller\Admin\PluginsController::activate()
     */
    public function testActivateExistingPlugin(): void
    {
        $table = $this->fetchTable('Plugins');

        $plugin = $table->newEntity([
            'name' => self::PLUGIN_NAME,
            'alias' => 'bare-bone',
            'description' => 'Test plugin',
            'enabled' => false,
        ]);

        $table->save($plugin);

        $this->post('/admin/plugins/activate/' . self::PLUGIN_NAME);

        $this->assertRedirectContains('/admin/plugins');
        $this->assertFlashMessage('The plugin has been activated.');
        $this->assertTrue($table->exists(['name' => self::PLUGIN_NAME]));

        //Check if enabled field is now true
        $updatedPlugin = $table->get($plugin->id);
        $this->assertTrue($updatedPlugin->enabled, 'Plugin should be enabled after activation.');
    }

    /**
     * Test uninstall new plugin success method
     *
     * @return void
     * @uses \App\Controller\Admin\PluginsController::uninstall()
     */
    public function testUninstallNewPlugin(): void
    {
        $zipPath = TESTS . 'Fixture/data/' . self::PLUGIN_NAME . '.zip';

        $zip = new ZipArchive();
        $res = $zip->open($zipPath);
        $this->assertTrue($res === true, 'ZIP file could not be opened');
        $zip->extractTo(self::PLUGINS_DIR);
        $zip->close();

        $this->assertDirectoryExists(self::PLUGINS_DIR, 'Plugin directory should exist after extraction');

        // Mock ComposerManager
        $this->mockService(ComposerManager::class, function () {
            $mock = $this->createMock(ComposerManager::class);
            $mock->expects($this->once())
                ->method('dumpAutoload')
                ->with(['--optimize' => true]);

            return $mock;
        });

        $this->post('/admin/plugins/uninstall/' . self::PLUGIN_NAME);

        $this->assertRedirectContains('/admin/plugins');
        $this->assertDirectoryDoesNotExist(ROOT . '/plugins/' . self::PLUGIN_NAME, 'The plugin directory does exist.');
        $this->assertFlashMessage('The plugin has been uninstalled.');

        $plugin = $this->fetchTable('Plugins')->find()->where(['name' => self::PLUGIN_NAME])->first();
        $this->assertNull($plugin);
    }

    /**
     * Test uninstall existing plugin success method
     *
     * @return void
     * @uses \App\Controller\Admin\PluginsController::uninstall()
     */
    public function testUninstallExistingPlugin(): void
    {
        $pluginsTable = $this->fetchTable('Plugins');
        $pluginsTable->save($pluginsTable->newEntity([
            'name' => self::PLUGIN_NAME,
            'alias' => 'bare-bone',
            'description' => 'Test plugin',
            'enabled' => true,
        ]));

        // Mock PluginManager
        $this->mockService(PluginManager::class, function () {
            $mock = $this->createMock(PluginManager::class);
            $mock->expects($this->once())
                ->method('uninstall')
                ->with(self::PLUGIN_NAME);

            return $mock;
        });

        // Mock ComposerManager
        $this->mockService(ComposerManager::class, function () {
            $mock = $this->createMock(ComposerManager::class);
            $mock->expects($this->once())
                ->method('dumpAutoload')
                ->with(['--optimize' => true]);

            return $mock;
        });

        $this->post('/admin/plugins/uninstall/' . self::PLUGIN_NAME);

        $this->assertRedirectContains('/admin/plugins');
        $this->assertFlashMessage('The plugin has been uninstalled.');

        $plugin = $pluginsTable->find()->where(['name' => self::PLUGIN_NAME])->first();
        $this->assertNull($plugin, 'Plugin should be removed from the database.');
    }

    /**
     * Test deactivate plugin success method
     *
     * @return void
     * @uses \App\Controller\Admin\PluginsController::deactivate()
     */
    public function testDeactivatePluginSuccess(): void
    {
        $pluginsTable = $this->fetchTable('Plugins');
        $plugin = $pluginsTable->newEntity([
            'name' => self::PLUGIN_NAME,
            'alias' => 'bare-bone',
            'description' => 'Deactivatable Plugin',
            'enabled' => true,
        ]);
        $pluginsTable->save($plugin);

        $this->post("/admin/plugins/deactivate/{$plugin->id}");

        $this->assertRedirectContains('/admin/plugins');
        $this->assertFlashMessage('The plugin has been deactivated.');

        $plugin = $pluginsTable->get($plugin->id);
        $this->assertFalse($plugin->enabled);
    }

    // -------------------------------------------------------------------------
    // Additional tests
    // -------------------------------------------------------------------------

    /**
     * testEditPost should persist the changed data.
     */
    public function testEditPostSavesData(): void
    {
        $this->post('/admin/plugins/edit/1', ['alias' => 'new-alias']);

        $this->assertResponseCode(302);
        $plugin = $this->fetchTable('Plugins')->get(1);
        $this->assertSame('new-alias', $plugin->alias);
    }

    /**
     * install() with a non-ZIP file should show an error flash.
     * PluginManager::install() throws when the file is not a valid ZIP.
     */
    public function testInstallInvalidFileShowsError(): void
    {
        $tmpName = tempnam(sys_get_temp_dir(), 'php');
        file_put_contents($tmpName, 'not a zip');

        $this->mockService(PluginManager::class, function () {
            $mock = $this->createMock(PluginManager::class);
            $mock->expects($this->once())
                ->method('install')
                ->willThrowException(new Exception('Invalid ZIP file.'));

            return $mock;
        });

        $zipFile = new UploadedFile(
            $tmpName,
            filesize($tmpName),
            UPLOAD_ERR_OK,
            'invalid.zip',
            'application/octet-stream',
        );

        $this->configRequest(['files' => ['plugin' => $zipFile]]);
        $this->post('/admin/plugins/install');

        $this->assertResponseCode(302);
        $this->assertFlashMessage('Invalid ZIP file.');

        unlink($tmpName);
    }

    /**
     * deactivate() should refuse to deactivate the default dashboard plugin.
     */
    public function testDeactivateDefaultDashboardShowsError(): void
    {
        // Enable the plugin first so we can verify it stays enabled after the blocked deactivation
        $table = $this->fetchTable('Plugins');
        $plugin = $table->get(1);
        $plugin->enabled = true;
        $table->save($plugin);

        Configure::write('Cms.defaultDashboard', 'TestPlugin1');

        $this->post('/admin/plugins/deactivate/1');

        $this->assertResponseCode(302);
        $this->assertFlashMessage('The plugin "TestPlugin1" cannot be modified. Its dashboard is set as the default one.');

        // Plugin should still be enabled — deactivation was blocked
        $this->assertTrue((bool)$table->get(1)->enabled);

        Configure::delete('Cms.defaultDashboard');
    }

    /**
     * uninstall() should refuse to uninstall the default dashboard plugin.
     */
    public function testUninstallDefaultDashboardShowsError(): void
    {
        Configure::write('Cms.defaultDashboard', 'TestPlugin1');

        $this->mockService(PluginManager::class, function () {
            $mock = $this->createMock(PluginManager::class);
            $mock->expects($this->never())->method('uninstall');

            return $mock;
        });
        $this->mockService(ComposerManager::class, function () {
            $mock = $this->createMock(ComposerManager::class);
            $mock->expects($this->never())->method('dumpAutoload');

            return $mock;
        });

        $this->post('/admin/plugins/uninstall/TestPlugin1');

        $this->assertResponseCode(302);
        $this->assertFlashMessage('The plugin "TestPlugin1" cannot be modified. Its dashboard is set as the default one.');

        // Plugin record should still exist
        $this->assertTrue($this->fetchTable('Plugins')->exists(['name' => 'TestPlugin1']));

        Configure::delete('Cms.defaultDashboard');
    }

    /**
     * activate() for an existing plugin should set enabled=true.
     * PluginManager::activate() should NOT be called — plugin already exists in DB.
     */
    public function testActivateExistingPluginDoesNotCallPluginManager(): void
    {
        // Set plugin as disabled
        $table = $this->fetchTable('Plugins');
        $plugin = $table->get(1);
        $plugin->enabled = false;
        $table->save($plugin);

        $this->mockService(PluginManager::class, function () {
            $mock = $this->createMock(PluginManager::class);
            $mock->expects($this->never())->method('activate');

            return $mock;
        });

        $this->post('/admin/plugins/activate/TestPlugin1');

        $this->assertResponseCode(302);
        $this->assertFlashMessage('The plugin has been activated.');
        $this->assertTrue((bool)$table->get(1)->enabled);
    }
}
