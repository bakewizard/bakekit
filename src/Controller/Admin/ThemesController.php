<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Exception;
use Laminas\Diactoros\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

/**
 * Themes Controller
 */
class ThemesController extends AppController
{

    private $themesDir = ROOT . DS . 'themes' . DS;

    public function index()
    {
        $activeTheme = $this->getConfig('Cms.theme');
        $themes = [];

        if (is_dir($this->themesDir)) {
            $dir = new \DirectoryIterator($this->themesDir);
            foreach ($dir as $info) {
                if (!$info->isDir() || $info->isDot()) {
                    continue;
                }

                $name = $info->getFilename();
                $configFile = $this->themesDir . $name . DS . 'composer.json';
                if (is_file($configFile)) {
                    $content = file_get_contents($configFile);
                    $json = json_decode($content, true);
                    $themes[$name] = [
                        'name' => strpos($json['name'], '/') ? substr($json['name'], strpos($json['name'], '/') + 1) : $json['name'],
                        'description' => $json['description']
                    ];
                }
            }
        }

        ksort($themes);

        $this->set([
            'activeTheme' => $activeTheme,
            'themes' => $themes
        ]);
    }

    /**
     * View method
     *
     * @param string $name Theme name.
     * @return \Cake\Http\Response|void
     */
    public function view($name)
    {
        $themePath = $this->themesDir . $name . DIRECTORY_SEPARATOR;
        $activeTheme = $this->getConfig('Cms.theme');

        $content = file_get_contents($themePath . 'composer.json');
        $json = json_decode($content, true);
        $theme = [
            'name' => strpos($json['name'], '/') ? substr($json['name'], strpos($json['name'], '/') + 1) : $json['name'],
            'description' => $json['description'],
            'license' => $json['license']
        ];

        $this->set([
            'activeTheme' => $activeTheme,
            'theme' => $theme
        ]);
    }

    /**
     * Install method
     */
    public function install()
    {
        $this->request->allowMethod(['post', 'put']);

        $file = $this->request->getUploadedFile('theme');
        $error = $file->getError();

        if ($error) {
            $this->Flash->error(UploadedFile::ERROR_MESSAGES[$error]);
            return $this->redirect(['action' => 'index']);
        }

        $theme = basename($file->getClientFilename(), '.zip');

        if (is_dir($this->themesDir . $theme)) {
            $this->Flash->error(__('Folder with the name "{0}" already exists', $theme));
            return $this->redirect(['action' => 'index']);
        }

        try {
            $this->unpack($file->getStream()->getMetadata('uri'), $this->themesDir);
            $this->Flash->success(__('The theme has been installed.'));
        } catch (Exception $e) {
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Uninstall method
     *
     * @param string Theme name.
     * @return \Cake\Http\Response|null Redirects to index.
     */
    public function uninstall($name)
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

    public function activate($name = null)
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
     * @throws Exception
     */
    private function unpack(string $input, string $output): void
    {
        $archive = new ZipArchive();

        if (!$archive->open($input)) {
            throw new Exception(__('Error occured while opening the archive.'));
        }

        if (!$archive->extractTo($output)) {
            $archive->close();
            throw new Exception(__('Error occured while extracting the archive.'));
        }
        $archive->close();
    }
}
