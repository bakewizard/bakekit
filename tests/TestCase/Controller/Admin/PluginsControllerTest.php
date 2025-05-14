<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;
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
        $this->assertFileExists($zipPath, 'The plugin zip file does not exist.');

        $zipFile = new UploadedFile(
            $zipPath,
            filesize($zipPath),
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
        $this->assertFlashMessage('The plugin has been installed.');
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

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/plugins');
        $this->assertFlashMessage('No file was uploaded');
    }

    /**
     * Test install method with missing file field
     *
     * @return void
     * @uses \App\Controller\Admin\PluginsController::install()
     */
    public function testInstallMissingFileField(): void
    {
        $this->post('/admin/plugins/install');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin/plugins');
        $this->assertFlashMessage('No plugin upload field was submitted.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\Admin\PluginsController::uninstall()
     */
    // public function testuninstall(): void
    // {
    //     $this->post('/admin/plugins/delete/1');
    //     $this->assertResponseCode(302);
    //     $this->assertRedirectContains('/admin/plugins');
    // }
}
