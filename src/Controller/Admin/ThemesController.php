<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use DirectoryIterator;
use Exception;
use Laminas\Diactoros\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

/**
 * Themes Controller
 *
 * @property \Search\Controller\Component\SearchComponent $Search
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class ThemesController extends AppController
{
    /**
     * Path to the themes directory.
     *
     * @var string
     */
    private string $themesDir = ROOT . DS . 'themes' . DS;

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $activeTheme = $this->getConfig('Cms.theme');
        $themes = [];

        if (is_dir($this->themesDir)) {
            $dir = new DirectoryIterator($this->themesDir);
            foreach ($dir as $info) {
                if (!$info->isDir() || $info->isDot()) {
                    continue;
                }

                $name = $info->getFilename();
                $config = $this->getConfigData($this->themesDir . $name);

                if (empty($config)) {
                    continue;
                }

                $themes[$name] = [
                    'name' => $name,
                    'description' => $config['description'] ?? '--- No description ---',
                    'license' => $config['license'] ?? '--- No license ---',
                ];
            }
        }

        ksort($themes);

        $this->set([
            'activeTheme' => $activeTheme,
            'themes' => $themes,
        ]);
    }

    /**
     * View method
     *
     * @param string $name Theme name.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(string $name)
    {
        $activeTheme = $this->getConfig('Cms.theme');

        $config = $this->getConfigData($this->themesDir . $name);

        $theme = [
            'name' => $name,
            'description' => $config['description'] ?? '--- No description ---',
            'license' => $config['license'] ?? '--- No license ---',
        ];

        $this->set([
            'activeTheme' => $activeTheme,
            'theme' => $theme,
        ]);
    }

    /**
     * Install method
     *
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Exception
     */
    public function install()
    {
        $this->request->allowMethod(['post', 'put']);

        $file = $this->request->getUploadedFile('theme');
        $error = $file->getError();

        if ($error) {
            $message = UploadedFile::ERROR_MESSAGES[$error] ?? 'Unknown upload error.';
            $this->Flash->error($message);

            return $this->redirect(['action' => 'index']);
        }

        // 1. Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            $this->Flash->error(__('Failed to check MIME type.'));

            return $this->redirect(['action' => 'index']);
        }
        $mimeType = finfo_file($finfo, $file->getStream()->getMetadata('uri'));
        finfo_close($finfo);

        if ($mimeType !== 'application/zip') {
            $this->Flash->error(__('Uploaded file is not a valid ZIP archive.'));

            return $this->redirect(['action' => 'index']);
        }

        $theme = basename($file->getClientFilename(), '.zip');

        // 2. Validate theme name
        if (!preg_match('/^[A-Z][a-zA-Z0-9]+$/', $theme)) {
            $this->Flash->error(__('Invalid theme name.'));

            return $this->redirect(['action' => 'index']);
        }

        // 3. Prevent overwriting existing folders
        if (is_dir($this->themesDir . $theme)) {
            $this->Flash->error(__('Folder with the name "{0}" already exists.', $theme));

            return $this->redirect(['action' => 'index']);
        }

        // 4. Move uploaded file to a safe temporary location
        $tempPath = TMP . uniqid('theme_', true) . '.zip';
        $file->moveTo($tempPath);

        // 5. Extracting
        try {
            $this->unpack($tempPath, $this->themesDir);
            $this->Flash->success(__('The theme has been installed.'));
        } catch (Exception $e) {
            $this->Flash->error($e->getMessage());
        } finally {
            // Always delete temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Uninstall method
     *
     * @param string $name Theme name.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function uninstall(string $name)
    {
        $this->request->allowMethod(['post', 'delete']);

        $activeTheme = Configure::read('Cms.theme');
        $themePath = $this->themesDir . $name . DS;

        if ($name === $activeTheme) {
            $this->Flash->error(__('Active theme cannot be uninstalled.'));
        } else {
            $fs = new Filesystem();

            try {
                if ($fs->exists($themePath)) {
                    $fs->remove($themePath);
                    $this->Flash->success(__('The theme has been uninstalled.'));
                }
            } catch (Exception $e) {
                $this->Flash->error($e->getMessage());
            }
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Activate method
     *
     * @param string|null $name Theme name.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function activate(?string $name = null)
    {
        Configure::write('theme', $name);
        Configure::dump('Cms', 'db', ['theme']);
        Cache::delete('settings', 'cms');

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Extracts an archive to a folder.
     *
     * @param string $input Input path.
     * @param string $output Output path.
     * @return void
     * @throws \Exception
     */
    private function unpack(string $input, string $output): void
    {
        if (!is_dir($output)) {
            throw new Exception(__('Themes directory does not exist.'));
        }

        if (!is_writable($output)) {
            throw new Exception(__('Themes directory is not writable.'));
        }

        $archive = new ZipArchive();
        $result = $archive->open($input);

        if ($result !== true) {
            throw new Exception(__('Failed to open archive. Error code: {0}', $result));
        }

        if (!$archive->extractTo($output)) {
            $archive->close();
            throw new Exception(__('Error occured while extracting the archive.'));
        }
        $archive->close();
    }

    /**
     * Reads composer.json into array.
     *
     * @param string $path
     * @return array<string, mixed>
     */
    private function getConfigData(?string $path = null): array
    {
        if (!$path) {
            $path = ROOT;
        }

        $configFile = $path . DS . 'composer.json';

        if (!file_exists($configFile) || !is_readable($configFile)) {
            return [];
        }

        $content = file_get_contents($configFile);
        if ($content === false) {
            return [];
        }

        return json_decode($content, true);
    }
}
